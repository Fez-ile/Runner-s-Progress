<?php
session_start();


if (!isset($_SESSION['historical_data'])) {
    $_SESSION['historical_data'] = [];
}

// Constants
define('MARATHON_DISTANCE', 50);
define('DEFAULT_TARGET_TIME', 4);
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
        return 0;
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

    return $data;
}

// Process form submission
$errors = [];
$results = null;

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
    }
}

// Load historical data
$historicalData = loadHistoricalData();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Runner's Progress Tracker</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fff5f5 0%, #ffecec 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #e53935 0%, #c62828 100%);
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
            border-color: #e53935;
        }

        .btn {
            background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
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
            color: #d32f2f;
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
            background: #e53935;
            color: white;
            font-weight: 600;
        }

        .history-table tr:nth-child(even) {
            background: #f2f2f2;
        }

        .history-table tr:hover {
            background: #ffebee;
        }

        .speed-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏃‍♂️ Runner's Progress Tracker</h1>
            <p>Track your marathon progress and calculate required pace</p>
            <p>The faster you run, the sooner you can stop running. Logic! 😎</p>
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

            <!-- Results -->
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

<footer>
    <p>&copy; 2025 Fez_Dev. All rights reserved.</p>
</footer>

</html>
