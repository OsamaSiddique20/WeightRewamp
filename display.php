<?php
require 'auth.php';
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
            </div>
        </nav>

        <header class="card-header">
            <p class="eyebrow">Your dashboard</p>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>.</h1>
            <p class="lead">Track the long-term signal without losing sight of your latest momentum.</p>
        </header>

        <section class="stats-grid">
            <div class="stat">
                <span>Last 2 weeks</span>
                <strong id="twoWeekStat">...</strong>
            </div>
            <div class="stat">
                <span>Total change</span>
                <strong id="weightDifference">...</strong>
            </div>
            <div class="stat">
                <span>Entries</span>
                <strong id="entryCount">...</strong>
            </div>
        </section>

        <section class="panel dashboard-card">
            <div class="chart-head">
                <div>
                    <h2>Weight trend</h2>
                    <p class="lead" id="weightLossMessage">Calculating your recent change...</p>
                </div>
                <span class="status-pill" id="latestWeight">Latest: ...</span>
            </div>
            <div class="chart-wrap">
                <canvas id="myChart"></canvas>
                <div class="empty-state" id="emptyState">Add at least two entries to unlock a useful trend chart.</div>
            </div>
        </section>
    </main>

    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        let chart;

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

        // Function to fetch data and update the chart
        function fetchDataAndDrawChart() {
            fetch('fetch_data.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch data');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    const emptyState = document.getElementById('emptyState');
                    const entryCountElement = document.getElementById('entryCount');
                    entryCountElement.textContent = data.length;

                    if (data.length === 0) {
                        emptyState.classList.add('is-visible');
                        document.getElementById('weightLossMessage').textContent = 'No entries yet. Add your first weigh-in to begin.';
                        document.getElementById('twoWeekStat').textContent = '0 kg';
                        document.getElementById('weightDifference').textContent = '0 kg';
                        document.getElementById('latestWeight').textContent = 'Latest: none';
                        return;
                    }

                    emptyState.classList.remove('is-visible');

                    const datetimes = data.map(item => new Date(item.datetime));
                    const weights = data.map(item => Number(item.weight));

                    // Calculate weight change in the last 2 weeks
                    const now = new Date();
                    const twoWeeksAgo = new Date();
                    twoWeeksAgo.setDate(now.getDate() - 14);

                    const recentWeights = datetimes
                        .map((date, index) => ({ date, weight: weights[index] }))
                        .filter(item => item.date >= twoWeeksAgo);

                    const weightLossElement = document.getElementById("weightLossMessage");
                    const twoWeekStatElement = document.getElementById("twoWeekStat");
                    if (recentWeights.length > 1) {
                        const startWeight = recentWeights[0].weight;
                        const endWeight = recentWeights[recentWeights.length - 1].weight;
                        const weightChange = endWeight - startWeight;
                        const formattedChange = `${weightChange > 0 ? '+' : ''}${weightChange.toFixed(1)} kg`;

                        if (weightChange < 0) {
                            weightLossElement.textContent = `You are down ${Math.abs(weightChange).toFixed(1)} kg in the last 2 weeks.`;
                        } else if (weightChange > 0) {
                            weightLossElement.textContent = `You are up ${weightChange.toFixed(1)} kg in the last 2 weeks.`;
                        } else {
                            weightLossElement.textContent = `Your weight held steady in the last 2 weeks.`;
                        }
                        twoWeekStatElement.textContent = formattedChange;
                        setTone(twoWeekStatElement, weightChange);
                    } else {
                        weightLossElement.textContent = "Add another recent entry to calculate your 2-week change.";
                        twoWeekStatElement.textContent = 'Need 2';
                        twoWeekStatElement.classList.add('is-neutral');
                    }

                    // Calculate overall weight difference
                    const firstWeight = weights[0];
                    const lastWeight = weights[weights.length - 1];
                    const weightDifference = lastWeight - firstWeight;

                    const weightDifferenceElement = document.getElementById('weightDifference');
                    if (weightDifferenceElement) {
                        weightDifferenceElement.textContent = `${weightDifference > 0 ? '+' : ''}${weightDifference.toFixed(1)} kg`;
                        setTone(weightDifferenceElement, weightDifference);
                    }

                    document.getElementById('latestWeight').textContent = `Latest: ${lastWeight.toFixed(1)} kg`;

                    // Initialize or update the chart
                    if (chart) {
                        chart.data.labels = datetimes.map(date => date.toISOString().split('T')[0]);
                        chart.data.datasets[0].data = weights;
                        chart.update();
                    } else {
                        chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: datetimes.map(date => date.toISOString().split('T')[0]),
                                datasets: [{
                                    label: 'Weight (kg)',
                                    data: weights,
                                    fill: true,
                                    borderColor: '#2563eb',
                                    backgroundColor: 'rgba(37, 99, 235, 0.10)',
                                    pointBackgroundColor: '#ffffff',
                                    pointBorderColor: '#2563eb',
                                    pointBorderWidth: 3,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    tension: 0.36
                                }]
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
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: '#111827',
                                        padding: 12,
                                        titleFont: {
                                            weight: '700'
                                        },
                                        callbacks: {
                                            label: context => `${context.parsed.y.toFixed(1)} kg`
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: '#6b7280'
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
                    document.getElementById('weightLossMessage').textContent = 'Could not load your weight data right now.';
                });
        }

        // Initial data fetch and chart creation
        fetchDataAndDrawChart();

        // Refresh data at regular intervals
        setInterval(fetchDataAndDrawChart, 60000); // Fetch data every 60 seconds
    </script>
</body>
</html>
