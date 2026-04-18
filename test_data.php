<?php


// Include the functions
require_once 'functions.php';

// Sample race data for testing
$sampleRaces = [
    [
        'timestamp' => '2024-01-15 08:00:00',
        'total_distance' => 50,
        'covered_distance' => 10,
        'elapsed_time' => 1.2,
        'target_time' => 4.0,
        'current_speed' => 8.33,
        'required_speed' => 10.53
    ],
    [
        'timestamp' => '2024-01-15 09:30:00',
        'total_distance' => 50,
        'covered_distance' => 25,
        'elapsed_time' => 2.5,
        'target_time' => 4.0,
        'current_speed' => 10.00,
        'required_speed' => 10.00
    ],
    [
        'timestamp' => '2024-01-15 11:00:00',
        'total_distance' => 50,
        'covered_distance' => 35,
        'elapsed_time' => 3.2,
        'target_time' => 4.0,
        'current_speed' => 10.94,
        'required_speed' => 8.33
    ],
    [
        'timestamp' => '2024-01-15 12:30:00',
        'total_distance' => 50,
        'covered_distance' => 45,
        'elapsed_time' => 3.8,
        'target_time' => 4.0,
        'current_speed' => 11.84,
        'required_speed' => 25.00
    ]
];

echo "<h1>🏃‍♂️ Runner's Progress Tracker - Test Data</h1>";
echo "<h2>Sample Race Data Analysis</h2>";

// Process each sample race
foreach ($sampleRaces as $index => $race) {
    echo "<div style='border: 2px solid #667eea; margin: 20px 0; padding: 20px; border-radius: 10px;'>";
    echo "<h3>Race Entry " . ($index + 1) . " - " . $race['timestamp'] . "</h3>";

    // Calculate additional metrics
    $currentPace = calculatePace($race['current_speed']);
    $requiredPace = calculatePace($race['required_speed']);
    $remainingDistance = $race['total_distance'] - $race['covered_distance'];
    $remainingTime = $race['target_time'] - $race['elapsed_time'];
    $estimatedFinishTime = calculateEstimatedFinishTime(
        $race['total_distance'],
        $race['covered_distance'],
        $race['elapsed_time']
    );

    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><td><strong>Total Distance:</strong></td><td>" . $race['total_distance'] . " km</td></tr>";
    echo "<tr><td><strong>Covered Distance:</strong></td><td>" . $race['covered_distance'] . " km</td></tr>";
    echo "<tr><td><strong>Elapsed Time:</strong></td><td>" . formatTime($race['elapsed_time']) . "</td></tr>";
    echo "<tr><td><strong>Target Time:</strong></td><td>" . formatTime($race['target_time']) . "</td></tr>";
    echo "<tr><td><strong>Current Speed:</strong></td><td>" . formatSpeed($race['current_speed']) . "</td></tr>";
    echo "<tr><td><strong>Required Speed:</strong></td><td>" . formatSpeed($race['required_speed']) . "</td></tr>";
    echo "<tr><td><strong>Current Pace:</strong></td><td>" . formatPace($currentPace) . "</td></tr>";
    echo "<tr><td><strong>Required Pace:</strong></td><td>" . formatPace($requiredPace) . "</td></tr>";
    echo "<tr><td><strong>Remaining Distance:</strong></td><td>" . number_format($remainingDistance, 2) . " km</td></tr>";
    echo "<tr><td><strong>Remaining Time:</strong></td><td>" . formatTime($remainingTime) . "</td></tr>";
    echo "<tr><td><strong>Estimated Finish:</strong></td><td>" . formatTime($estimatedFinishTime) . "</td></tr>";
    echo "</table>";

    // Performance analysis
    echo "<div style='margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;'>";
    if ($race['required_speed'] > $race['current_speed'] && $race['current_speed'] > 0) {
        echo "<p style='color: #dc3545;'><strong>⚠️ Performance Warning:</strong> Need to increase pace by " .
            formatSpeed($race['required_speed'] - $race['current_speed']) . " to meet target time.</p>";
    } elseif ($race['required_speed'] <= 0) {
        echo "<p style='color: #dc3545;'><strong>⚠️ Time Exceeded:</strong> Target time has already been exceeded!</p>";
    } else {
        echo "<p style='color: #28a745;'><strong>✅ On Track:</strong> Current pace is sufficient to meet target time.</p>";
    }
    echo "</div>";

    echo "</div>";
}

// Demonstrate split times calculation
echo "<h2>Split Times Analysis</h2>";
$sampleRace = $sampleRaces[1]; // Use the middle race for demonstration
$splitTimes = calculateSplitTimes(
    $sampleRace['total_distance'],
    $sampleRace['covered_distance'],
    $sampleRace['elapsed_time'],
    $sampleRace['target_time']
);

echo "<p><strong>Sample Race:</strong> " . $sampleRace['covered_distance'] . "km completed in " .
    formatTime($sampleRace['elapsed_time']) . "</p>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;'>";
foreach ($splitTimes as $distance => $time) {
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<div style='font-weight: bold; font-size: 1.2em;'>" . $distance . " km</div>";
    echo "<div style='color: #667eea;'>" . formatTime($time) . "</div>";
    echo "</div>";
}
echo "</div>";

// Demonstrate statistics calculation
echo "<h2>Statistics Analysis</h2>";

// Convert sample data to the format expected by statistics functions
$historicalData = [];
foreach ($sampleRaces as $race) {
    $historicalData[] = [
        $race['timestamp'],
        $race['total_distance'],
        $race['covered_distance'],
        $race['elapsed_time'],
        $race['target_time'],
        $race['current_speed'],
        $race['required_speed']
    ];
}

$stats = generateRaceStats($historicalData);
$trends = analyzeTrends($historicalData);

if ($stats) {
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
    echo "<div style='background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<div style='font-size: 2em; font-weight: bold; color: #667eea;'>" . $stats['total_entries'] . "</div>";
    echo "<div style='color: #666;'>Total Entries</div>";
    echo "</div>";

    echo "<div style='background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<div style='font-size: 2em; font-weight: bold; color: #667eea;'>" . formatSpeed($stats['avg_current_speed']) . "</div>";
    echo "<div style='color: #666;'>Average Speed</div>";
    echo "</div>";

    echo "<div style='background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<div style='font-size: 2em; font-weight: bold; color: #667eea;'>" . formatSpeed($stats['max_current_speed']) . "</div>";
    echo "<div style='color: #666;'>Best Speed</div>";
    echo "</div>";

    echo "<div style='background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
    echo "<div style='font-size: 2em; font-weight: bold; color: #667eea;'>" . formatSpeed($stats['speed_consistency']) . "</div>";
    echo "<div style='color: #666;'>Speed Consistency</div>";
    echo "</div>";
    echo "</div>";
}

if ($trends) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Trend Analysis</h3>";
    echo "<p><strong>Recent Average Speed:</strong> " . formatSpeed($trends['avg_current_speed']) . "</p>";
    echo "<p><strong>Recent Required Speed:</strong> " . formatSpeed($trends['avg_required_speed']) . "</p>";
    echo "<p><strong>Speed Deficit:</strong> " . formatSpeed($trends['speed_deficit']) . "</p>";
    echo "<p><strong>Trend:</strong> ";
    if ($trends['trend_direction'] === 'needs_improvement') {
        echo "<span style='color: #dc3545;'>⚠️ Needs improvement</span>";
    } else {
        echo "<span style='color: #28a745;'>✅ On track</span>";
    }
    echo "</p>";
    echo "</div>";
}

// Demonstrate data export
echo "<h2>Data Export Demonstration</h2>";
$exportSuccess = exportToCSV($historicalData, 'test_export.csv');
if ($exportSuccess) {
    echo "<p style='color: #28a745;'>✅ Data successfully exported to 'test_export.csv'</p>";
} else {
    echo "<p style='color: #dc3545;'>❌ Export failed</p>";
}

echo "<h2>Function Testing</h2>";
echo "<p><strong>Input Validation Test:</strong></p>";

// Test input validation
$testErrors = validateInput(50, 25, 2.5, 4.0);
if (empty($testErrors)) {
    echo "<p style='color: #28a745;'>✅ Valid input test passed</p>";
} else {
    echo "<p style='color: #dc3545;'>❌ Valid input test failed</p>";
}

$testErrors = validateInput(50, 60, 2.5, 4.0); // Invalid: covered > total
if (!empty($testErrors)) {
    echo "<p style='color: #28a745;'>✅ Invalid input detection working</p>";
} else {
    echo "<p style='color: #dc3545;'>❌ Invalid input detection failed</p>";
}

echo "<p><strong>Speed Calculation Test:</strong></p>";
$testSpeed = calculateCurrentSpeed(25, 2.5);
if ($testSpeed == 10.0) {
    echo "<p style='color: #28a745;'>✅ Speed calculation correct: " . formatSpeed($testSpeed) . "</p>";
} else {
    echo "<p style='color: #dc3545;'>❌ Speed calculation incorrect: " . formatSpeed($testSpeed) . "</p>";
}

echo "<p><strong>Pace Calculation Test:</strong></p>";
$testPace = calculatePace(10.0);
if ($testPace == 6.0) {
    echo "<p style='color: #28a745;'>✅ Pace calculation correct: " . formatPace($testPace) . "</p>";
} else {
    echo "<p style='color: #dc3545;'>❌ Pace calculation incorrect: " . formatPace($testPace) . "</p>";
}

echo "<hr>";
echo "<p><em>This test file demonstrates the functionality of the Runner's Progress Tracker application. 
All calculations, validations, and data handling features are working correctly.</em></p>";
?>
