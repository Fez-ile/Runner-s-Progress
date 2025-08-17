<?php
session_start();

// Include utility functions
require_once 'functions.php';

// Initialize historical data array if not exists
if (!isset($_SESSION['historical_data'])) {
    $_SESSION['historical_data'] = [];
}

// Constants
define('MARATHON_DISTANCE', 50); // 50km
define('DEFAULT_TARGET_TIME', 4); // 4 hours default
define('DATA_FILE', 'race_data.txt');

// Function to calculate current average speed
function calculateCurrentSpeed($coveredDistance, $elapsedTime)
{
    if ($elapsedTime <= 0) {
        return 0;
    }
    return $coveredDistance / $elapsedTime; // km/h
}

// Function to calculate required speed to finish within target time
function calculateRequiredSpeed($totalDistance, $coveredDistance, $elapsedTime, $targetTime)
{
    $remainingDistance = $totalDistance - $coveredDistance;
    $remainingTime = $targetTime - $elapsedTime;

    if ($remainingTime <= 0) {
        return 0; // Target time already exceeded
    }

    return $remainingDistance / $remainingTime; // km/h
}

// Function to format speed with appropriate units
function formatSpeed($speed)
{
    if ($speed == 0) {
        return "0.00 km/h";
    }
    return number_format($speed, 2) . " km/h";
}

// Function to format time in hours and minutes
function formatTime($hours)
{
    $wholeHours = floor($hours);
    $minutes = round(($hours - $wholeHours) * 60);

    if ($wholeHours == 0) {
        return $minutes . " minutes";
    } elseif ($minutes == 0) {
        return $wholeHours . " hours";
    } else {
        return $wholeHours . " hours " . $minutes . " minutes";
    }
}

// Function to validate user input
function validateInput($totalDistance, $coveredDistance, $elapsedTime, $targetTime)
{
    $errors = [];

    if ($totalDistance <= 0) {
        $errors[] = "Total distance must be greater than 0";
    }

    if ($coveredDistance < 0) {
        $errors[] = "Covered distance cannot be negative";
    }

    if ($coveredDistance > $totalDistance) {
        $errors[] = "Covered distance cannot exceed total distance";
    }

    if ($elapsedTime < 0) {
        $errors[] = "Elapsed time cannot be negative";
    }

    if ($targetTime <= 0) {
        $errors[] = "Target time must be greater than 0";
    }

    if ($elapsedTime >= $targetTime) {
        $errors[] = "Elapsed time cannot be greater than or equal to target time";
    }

    return $errors;
}

// Function to save race data to file
function saveRaceData($raceData)
{
    try {
        $dataLine = implode(',', $raceData) . "\n";
        file_put_contents(DATA_FILE, $dataLine, FILE_APPEND | LOCK_EX);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to load historical data from file
function loadHistoricalData()
{
    if (!file_exists(DATA_FILE)) {
        return [];
    }

    $data = [];
    $lines = file(DATA_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $data[] = explode(',', $line);
    }

    return validateFileData($data);
}

// Process form submission
$errors = [];
$results = null;
$advancedResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $totalDistance = filter_input(INPUT_POST, 'total_distance', FILTER_VALIDATE_FLOAT);
    $coveredDistance = filter_input(INPUT_POST, 'covered_distance', FILTER_VALIDATE_FLOAT);
    $elapsedTime = filter_input(INPUT_POST, 'elapsed_time', FILTER_VALIDATE_FLOAT);
    $targetTime = filter_input(INPUT_POST, 'target_time', FILTER_VALIDATE_FLOAT);

    // Validate input
    $errors = validateInput($totalDistance, $coveredDistance, $elapsedTime, $targetTime);

    if (empty($errors)) {
        // Calculate speeds
        $currentSpeed = calculateCurrentSpeed($coveredDistance, $elapsedTime);
        $requiredSpeed = calculateRequiredSpeed($totalDistance, $coveredDistance, $elapsedTime, $targetTime);

        // Calculate additional metrics
        $currentPace = calculatePace($currentSpeed);
        $requiredPace = calculatePace($requiredSpeed);
        $estimatedFinishTime = calculateEstimatedFinishTime($totalDistance, $coveredDistance, $elapsedTime);
        $splitTimes = calculateSplitTimes($totalDistance, $coveredDistance, $elapsedTime, $targetTime);

        // Store in historical data array
        $raceData = [
            date('Y-m-d H:i:s'),
            $totalDistance,
            $coveredDistance,
            $elapsedTime,
            $targetTime,
            $currentSpeed,
            $requiredSpeed
        ];

        $_SESSION['historical_data'][] = $raceData;

        // Save to file
        saveRaceData($raceData);

        $results = [
            'current_speed' => $currentSpeed,
            'required_speed' => $requiredSpeed,
            'remaining_distance' => $totalDistance - $coveredDistance,
            'remaining_time' => $targetTime - $elapsedTime
        ];

        $advancedResults = [
            'current_pace' => $currentPace,
            'required_pace' => $requiredPace,
            'estimated_finish_time' => $estimatedFinishTime,
            'split_times' => $splitTimes
        ];
    }
}

// Load historical data
$historicalData = loadHistoricalData();

// Generate statistics and trends
$raceStats = generateRaceStats($historicalData);
$trends = analyzeTrends($historicalData);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Runner's Progress Tracker</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }

        .content {
            padding: 30px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .errors {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .results {
            background: #e8f5e8;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .advanced-results {
            background: #e3f2fd;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .result-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }

        .result-label {
            font-weight: 600;
            color: #333;
        }

        .result-value {
            font-weight: 500;
            color: #667eea;
        }

        .split-times {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .split-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .split-distance {
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }

        .split-time {
            color: #667eea;
            font-size: 1.2em;
            margin-top: 5px;
        }

        .stats-section {
            background: #fff3e0;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .history-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .history-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        .history-table tr:nth-child(even) {
            background: #f2f2f2;
        }

        .history-table tr:hover {
            background: #e3f2fd;
        }

        .speed-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .export-section {
            background: #f1f8e9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏃‍♂️ Enhanced Runner's Progress Tracker</h1>
            <p>Advanced marathon tracking with pace analysis and split times</p>
        </div>

        <div class="content">
            <!-- Input Form -->
            <div class="form-section">
                <h2>📊 Enter Race Details</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="total_distance">Total Marathon Distance (km):</label>
                        <input type="number" id="total_distance" name="total_distance"
                            value="<?php echo MARATHON_DISTANCE; ?>" step="0.1" required>
                    </div>

                    <div class="form-group">
                        <label for="covered_distance">Distance Covered (km):</label>
                        <input type="number" id="covered_distance" name="covered_distance" step="0.1" required>
                    </div>

                    <div class="form-group">
                        <label for="elapsed_time">Elapsed Time (hours):</label>
                        <input type="number" id="elapsed_time" name="elapsed_time" step="0.1" required>
                    </div>

                    <div class="form-group">
                        <label for="target_time">Target Time to Complete (hours):</label>
                        <input type="number" id="target_time" name="target_time"
                            value="<?php echo DEFAULT_TARGET_TIME; ?>" step="0.1" required>
                    </div>

                    <button type="submit" class="btn">Calculate Progress</button>
                </form>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <h3>⚠️ Input Errors:</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Basic Results -->
            <?php if ($results): ?>
                <div class="results">
                    <h2>📈 Race Analysis</h2>

                    <div class="result-item">
                        <span class="result-label">Current Average Speed:</span>
                        <span class="result-value"><?php echo formatSpeed($results['current_speed']); ?></span>
                    </div>

                    <div class="result-item">
                        <span class="result-label">Required Speed to Finish:</span>
                        <span class="result-value"><?php echo formatSpeed($results['required_speed']); ?></span>
                    </div>

                    <div class="result-item">
                        <span class="result-label">Remaining Distance:</span>
                        <span class="result-value"><?php echo number_format($results['remaining_distance'], 2); ?> km</span>
                    </div>

                    <div class="result-item">
                        <span class="result-label">Remaining Time:</span>
                        <span class="result-value"><?php echo formatTime($results['remaining_time']); ?></span>
                    </div>

                    <?php if ($results['required_speed'] > $results['current_speed'] && $results['current_speed'] > 0): ?>
                        <div class="speed-warning">
                            ⚠️ You need to increase your pace by
                            <?php echo formatSpeed($results['required_speed'] - $results['current_speed']); ?> to meet your
                            target time!
                        </div>
                    <?php elseif ($results['required_speed'] <= 0): ?>
                        <div class="speed-warning">
                            ⚠️ Your target time has already been exceeded!
                        </div>
                    <?php else: ?>
                        <div class="speed-warning" style="background: #d4edda; color: #155724;">
                            ✅ You're on track to meet your target time!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Advanced Results -->
            <?php if ($advancedResults): ?>
                <div class="advanced-results">
                    <h2>⚡ Advanced Metrics</h2>

                    <div class="result-item">
                        <span class="result-label">Current Pace:</span>
                        <span class="result-value"><?php echo formatPace($advancedResults['current_pace']); ?></span>
                    </div>

                    <div class="result-item">
                        <span class="result-label">Required Pace:</span>
                        <span class="result-value"><?php echo formatPace($advancedResults['required_pace']); ?></span>
                    </div>

                    <div class="result-item">
                        <span class="result-label">Estimated Finish Time:</span>
                        <span
                            class="result-value"><?php echo formatTime($advancedResults['estimated_finish_time']); ?></span>
                    </div>

                    <h3>Split Times (Every 10km)</h3>
                    <div class="split-times">
                        <?php foreach ($advancedResults['split_times'] as $distance => $time): ?>
                            <div class="split-item">
                                <div class="split-distance"><?php echo $distance; ?> km</div>
                                <div class="split-time"><?php echo formatTime($time); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <?php if ($raceStats): ?>
                <div class="stats-section">
                    <h2>📊 Race Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $raceStats['total_entries']; ?></div>
                            <div class="stat-label">Total Entries</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo formatSpeed($raceStats['avg_current_speed']); ?></div>
                            <div class="stat-label">Average Speed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo formatSpeed($raceStats['max_current_speed']); ?></div>
                            <div class="stat-label">Best Speed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo formatSpeed($raceStats['speed_consistency']); ?></div>
                            <div class="stat-label">Speed Consistency</div>
                        </div>
                    </div>

                    <?php if ($trends): ?>
                        <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 8px;">
                            <h3>Trend Analysis</h3>
                            <p><strong>Recent Average Speed:</strong> <?php echo formatSpeed($trends['avg_current_speed']); ?>
                            </p>
                            <p><strong>Recent Required Speed:</strong> <?php echo formatSpeed($trends['avg_required_speed']); ?>
                            </p>
                            <p><strong>Speed Deficit:</strong> <?php echo formatSpeed($trends['speed_deficit']); ?></p>
                            <p><strong>Trend:</strong>
                                <?php if ($trends['trend_direction'] === 'needs_improvement'): ?>
                                    <span style="color: #dc3545;">⚠️ Needs improvement</span>
                                <?php else: ?>
                                    <span style="color: #28a745;">✅ On track</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Export Section -->
            <div class="export-section">
                <h3>📁 Data Management</h3>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="export_csv">
                    <button type="submit" class="btn-secondary">Export to CSV</button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="backup_data">
                    <button type="submit" class="btn-secondary">Backup Data</button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cleanup_data">
                    <button type="submit" class="btn-secondary">Cleanup Old Data</button>
                </form>
            </div>

            <!-- Historical Data -->
            <?php if (!empty($historicalData)): ?>
                <div class="history-section">
                    <h2>📋 Race History</h2>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Total Distance</th>
                                <th>Covered Distance</th>
                                <th>Elapsed Time</th>
                                <th>Target Time</th>
                                <th>Current Speed</th>
                                <th>Required Speed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($historicalData) as $data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data[0]); ?></td>
                                    <td><?php echo htmlspecialchars($data[1]); ?> km</td>
                                    <td><?php echo htmlspecialchars($data[2]); ?> km</td>
                                    <td><?php echo htmlspecialchars($data[3]); ?> h</td>
                                    <td><?php echo htmlspecialchars($data[4]); ?> h</td>
                                    <td><?php echo formatSpeed($data[5]); ?></td>
                                    <td><?php echo formatSpeed($data[6]); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>