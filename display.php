<?php
require 'auth.php';
require 'db.php';

$userId = $_SESSION['user_id'];
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['redirect_time']);

// Fetch user weigh-in history (ascending for calculations; we will reverse for history display)
$stmt = $pdo->prepare('SELECT id, datetime, weight FROM user_info WHERE user_id = ? ORDER BY datetime ASC');
$stmt->execute([$userId]);
$entries = $stmt->fetchAll();
$displayEntries = array_reverse($entries);

$stmt = $pdo->prepare('SELECT goal_weight FROM user_settings WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$goalWeight = $stmt->fetchColumn();

$entryCount = count($entries);
$hasEntries = $entryCount > 0;
$currentWeight = null;
$highestWeight = null;
$lowestWeight = null;
$totalChange = null;
$sevenDayChange = null;
$thirtyDayChange = null;
$goalRemaining = null;
$goalProgress = null;
$goalSummary = null;
$projectedDate = null;

if ($hasEntries) {
    $weights = array_map(function ($entry) {
        return (float)$entry['weight'];
    }, $entries);

    $currentWeight = end($weights);
    $firstWeight = $weights[0];
    $highestWeight = max($weights);
    $lowestWeight = min($weights);
    $totalChange = $currentWeight - $firstWeight;

    $latestDate = new DateTime(end($entries)['datetime']);
    $cutoff7 = (clone $latestDate)->sub(new DateInterval('P7D'));
    $cutoff30 = (clone $latestDate)->sub(new DateInterval('P30D'));

    $recent7 = null;
    $recent30 = null;
    foreach (array_reverse($entries) as $entry) {
        $entryDate = new DateTime($entry['datetime']);
        if (!$recent7 && $entryDate <= $cutoff7) {
            $recent7 = $entry;
        }
        if (!$recent30 && $entryDate <= $cutoff30) {
            $recent30 = $entry;
        }
        if ($recent7 && $recent30) {
            break;
        }
    }

    if ($recent7) {
        $sevenDayChange = $currentWeight - (float)$recent7['weight'];
    }
    if ($recent30) {
        $thirtyDayChange = $currentWeight - (float)$recent30['weight'];
    }

    if ($goalWeight !== false && $goalWeight !== null) {
        $goalRemaining = $goalWeight - $currentWeight;
        $direction = ($goalWeight < $currentWeight) ? 'lose' : 'gain';

        if ($firstWeight === $goalWeight) {
            $goalProgress = 100;
        } else {
            if ($firstWeight > $goalWeight) {
                $goalProgress = 100 * (($firstWeight - $currentWeight) / ($firstWeight - $goalWeight));
            } else {
                $goalProgress = 100 * (($currentWeight - $firstWeight) / ($goalWeight - $firstWeight));
            }
            $goalProgress = max(0, min(100, $goalProgress));
        }

        $days = [];
        $xSum = 0;
        $ySum = 0;
        $xySum = 0;
        $xxSum = 0;
        $startDate = new DateTime($entries[0]['datetime']);

        foreach ($entries as $index => $entry) {
            $entryDate = new DateTime($entry['datetime']);
            $x = (int)$startDate->diff($entryDate)->format('%a');
            $y = (float)$entry['weight'];
            $xSum += $x;
            $ySum += $y;
            $xySum += $x * $y;
            $xxSum += $x * $x;
            $days[] = ['x' => $x, 'y' => $y];
        }

        $n = count($days);
        $den = $xxSum - ($xSum * $xSum / $n);
        $slope = $den === 0 ? 0 : ($xySum - ($xSum * $ySum / $n)) / $den;

        if ($slope !== 0) {
            $daysToGoal = ($goalWeight - $currentWeight) / $slope;
            if ($daysToGoal > 0) {
                $projectedDate = (clone $latestDate)->add(new DateInterval('P' . ceil($daysToGoal) . 'D'));
            }
        }

        if (abs($goalRemaining) < 0.05) {
            $goalSummary = 'At goal weight. Great work!';
        } else {
            $goalSummary = sprintf('%s %s kg to reach your target.',
                $goalRemaining > 0 ? 'Need to gain' : 'Need to lose',
                number_format(abs($goalRemaining), 1)
            );
        }

        if ($projectedDate) {
            $goalSummary .= ' Projected on ' . $projectedDate->format('M j, Y') . '.';
        } elseif ($slope === 0 && abs($goalRemaining) >= 0.05) {
            $goalSummary .= ' Need more trend data to estimate a target date.';
        }
    }
}

function formatWeightChange($value)
{
    if ($value === null) {
        return 'Need more data';
    }
    return ($value > 0 ? '+' : '') . number_format($value, 1) . ' kg';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weight Tracking Graph</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="app-shell">
        <nav class="nav">
            <a class="brand" href="index.php">
                <span class="brand-mark">W</span>
                <span>Weight Tracker</span>
            </a>
            <div class="nav-actions">
                <a class="button button-ghost" href="index.php">Add entry</a>
                <a class="button button-ghost" href="logout.php">Logout</a>
            </div>
        </nav>

        <header class="card-header">
            <p class="eyebrow">Your dashboard</p>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>.</h1>
            <p class="lead">Track the long-term signal without losing sight of your latest momentum.</p>
        </header>

        <?php if ($successMessage): ?>
            <p class="message message-success"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p class="message message-error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat">
                <span>Current weight</span>
                <strong><?php echo $hasEntries ? number_format($currentWeight, 1) . ' kg' : '—'; ?></strong>
            </div>
            <div class="stat">
                <span>Highest weight</span>
                <strong><?php echo $hasEntries ? number_format($highestWeight, 1) . ' kg' : '—'; ?></strong>
            </div>
            <div class="stat">
                <span>Lowest weight</span>
                <strong><?php echo $hasEntries ? number_format($lowestWeight, 1) . ' kg' : '—'; ?></strong>
            </div>
            <div class="stat">
                <span>7-day change</span>
                <strong><?php echo $hasEntries ? formatWeightChange($sevenDayChange) : '—'; ?></strong>
            </div>
            <div class="stat">
                <span>30-day change</span>
                <strong><?php echo $hasEntries ? formatWeightChange($thirtyDayChange) : '—'; ?></strong>
            </div>
            <div class="stat">
                <span>Total change</span>
                <strong><?php echo $hasEntries ? formatWeightChange($totalChange) : '—'; ?></strong>
            </div>
        </section>

        <section class="panel dashboard-card">
            <div class="chart-head">
                <div>
                    <h2>Weight trend</h2>
                    <p class="lead" id="weightLossMessage"><?php echo $hasEntries ? 'Your recent momentum is shown below. Edit entries anytime to keep the graph accurate.' : 'Add your first weigh-in to begin seeing your trend.'; ?></p>
                </div>
                <span class="status-pill" id="latestWeight"><?php echo $hasEntries ? 'Latest: ' . number_format($currentWeight, 1) . ' kg' : 'Latest: none'; ?></span>
            </div>

            <div class="goal-panel">
                <div class="goal-card">
                    <strong>Goal weight</strong>
                    <p><?php echo $goalWeight !== false && $goalWeight !== null ? number_format($goalWeight, 1) . ' kg' : 'Not set yet'; ?></p>
                    <?php if ($goalSummary): ?>
                        <p class="goal-description"><?php echo htmlspecialchars($goalSummary); ?></p>
                    <?php endif; ?>
                </div>
                <div class="goal-card">
                    <strong>Goal progress</strong>
                    <p><?php echo $goalWeight !== false && $goalWeight !== null && $hasEntries ? number_format($goalProgress, 0) . '% complete' : 'Set a target to track progress'; ?></p>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo $goalWeight !== false && $goalWeight !== null && $hasEntries ? max(0, min(100, $goalProgress)) : 0; ?>%;"></div>
                    </div>
                </div>
                <form class="goal-form" action="save_goal.php" method="post">
                    <label for="goal_weight">Target weight</label>
                    <div class="goal-input-row">
                        <input type="number" id="goal_weight" name="goal_weight" step="0.1" min="1" placeholder="72.0" value="<?php echo $goalWeight !== false ? htmlspecialchars($goalWeight) : ''; ?>">
                        <button class="button-primary" type="submit">Save goal</button>
                    </div>
                    <?php if ($goalWeight !== false && $goalWeight !== null): ?>
                        <button class="button button-secondary button-clear" type="submit" name="goal_weight" value="">Clear goal</button>
                    <?php endif; ?>
                </form>
            </div>

            <div class="filter-row">
                <div class="filter-buttons">
                    <button class="filter-button active" data-range="30">1 month</button>
                    <button class="filter-button" data-range="90">3 months</button>
                    <button class="filter-button" data-range="180">6 months</button>
                    <button class="filter-button" data-range="365">1 year</button>
                    <button class="filter-button" data-range="all">All time</button>
                </div>
            </div>

            <div class="chart-wrap">
                <canvas id="myChart"></canvas>
                <div class="empty-state <?php echo $hasEntries ? '' : 'is-visible'; ?>" id="emptyState">Add at least two entries to unlock a useful trend chart.</div>
            </div>
        </section>

        <section class="panel dashboard-card">
            <div class="card-header">
                <h2>Entry history</h2>
                <p>Review, edit, or remove any recorded weigh-in.</p>
            </div>
            <?php if ($hasEntries): ?>
                <div class="table-wrap">
                    <table class="entry-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayEntries as $entry): ?>
                                <tr data-date="<?php echo htmlspecialchars($entry['datetime']); ?>">
                                    <td><?php echo htmlspecialchars($entry['datetime']); ?></td>
                                    <td><?php echo number_format((float)$entry['weight'], 1); ?> kg</td>
                                    <td class="actions">
                                        <a class="button button-secondary" href="edit_entry.php?id=<?php echo $entry['id']; ?>">Edit</a>
                                        <form action="delete_entry.php" method="post" onsubmit="return confirm('Delete this entry?');">
                                            <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                            <button class="button button-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state is-visible">No weigh-ins recorded yet. Add your first measurement to begin.</div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        let chart;
        let selectedRange = '30'; // default: 1 month

        function filterEntriesByRange(data) {
            if (selectedRange === 'all') return data;
            const days = parseInt(selectedRange, 10);
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - days);
            return data.filter(item => new Date(item.datetime) >= cutoff);
        }

        function applyTableFilter() {
            const rows = document.querySelectorAll('.entry-table tbody tr');
            if (!rows) return;
            if (selectedRange === 'all') {
                rows.forEach(r => r.style.display = '');
                return;
            }
            const days = parseInt(selectedRange, 10);
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - days);
            rows.forEach(row => {
                const d = new Date(row.dataset.date);
                row.style.display = d >= cutoff ? '' : 'none';
            });
        }

        function setTone(element, value) {
            element.classList.remove('is-positive', 'is-negative', 'is-neutral');
            if (value < 0) {
                element.classList.add('is-positive');
            } else if (value > 0) {
                element.classList.add('is-negative');
            } else {
                element.classList.add('is-neutral');
            }
        }

        function fetchDataAndDrawChart() {
            fetch('fetch_data.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch data');
                    }
                    return response.json();
                })
                .then(data => {
                    const emptyState = document.getElementById('emptyState');

                    if (data.length === 0) {
                        emptyState.classList.add('is-visible');
                        document.getElementById('weightLossMessage').textContent = 'No entries yet. Add your first weigh-in to begin.';
                        document.getElementById('latestWeight').textContent = 'Latest: none';
                        return;
                    }

                    emptyState.classList.remove('is-visible');

                    const datetimes = data.map(item => new Date(item.datetime));
                    const weights = data.map(item => Number(item.weight));
                    const lastWeight = weights[weights.length - 1];

                    document.getElementById('latestWeight').textContent = `Latest: ${lastWeight.toFixed(1)} kg`;

                    const trendData = calculateTrendLine(weights);
                    const labels = datetimes.map(date => date.toISOString().split('T')[0]);

                    if (chart) {
                        chart.data.labels = labels;
                        chart.data.datasets[0].data = weights;
                        chart.data.datasets[1].data = trendData;
                        chart.update();
                    } else {
                        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
                        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.20)');
                        gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

                        chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [
                                    {
                                        label: 'Actual weight',
                                        data: weights,
                                        fill: true,
                                        borderColor: '#2563eb',
                                        backgroundColor: gradient,
                                        pointBackgroundColor: '#fff',
                                        pointBorderColor: '#2563eb',
                                        pointBorderWidth: 3,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        tension: 0.42,
                                        borderWidth: 3,
                                        cubicInterpolationMode: 'monotone'
                                    },
                                    {
                                        label: 'Trend line',
                                        data: trendData,
                                        borderColor: '#1d4ed8',
                                        borderWidth: 2,
                                        borderDash: [6, 6],
                                        pointRadius: 0,
                                        fill: false,
                                        tension: 0.42
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: {
                                            color: '#1f2937',
                                            usePointStyle: true,
                                            pointStyle: 'circle'
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: '#111827',
                                        padding: 12,
                                        titleFont: {
                                            weight: '700'
                                        },
                                        callbacks: {
                                            label: context => `${context.dataset.label}: ${context.parsed.y.toFixed(1)} kg`
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: '#6b7280',
                                            maxRotation: 0,
                                            autoSkip: true,
                                            maxTicksLimit: 8
                                        }
                                    },
                                    y: {
                                        grid: {
                                            color: '#eef2f7'
                                        },
                                        ticks: {
                                            color: '#6b7280',
                                            callback: value => `${value} kg`
                                        }
                                    }
                                }
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    const weightLossElement = document.getElementById('weightLossMessage');
                    if (weightLossElement) {
                        weightLossElement.textContent = 'Could not load your weight data right now.';
                    }
                });
        }

        function calculateTrendLine(values) {
            const n = values.length;
            if (n < 2) {
                return values.slice();
            }
            const xMean = (n - 1) / 2;
            const yMean = values.reduce((sum, value) => sum + value, 0) / n;
            let num = 0;
            let den = 0;
            for (let i = 0; i < n; i++) {
                num += (i - xMean) * (values[i] - yMean);
                den += (i - xMean) * (i - xMean);
            }
            const slope = den === 0 ? 0 : num / den;
            const intercept = yMean - slope * xMean;
            return values.map((_, idx) => slope * idx + intercept);
        }

        // Wire up filter buttons
        document.querySelectorAll('.filter-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.filter-button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedRange = btn.dataset.range;
                fetchDataAndDrawChart();
                applyTableFilter();
            });
        });

        // Initial render
        fetchDataAndDrawChart();
        applyTableFilter();
        setInterval(fetchDataAndDrawChart, 60000);
    </script>
</body>
</html>
