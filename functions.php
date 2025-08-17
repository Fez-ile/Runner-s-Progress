<?php
/**
 * Runner's Progress Tracker - Utility Functions
 * Additional functions for data analysis and advanced calculations
 */

// Function to calculate pace (time per kilometer)
function calculatePace($speed)
{
    if ($speed <= 0) {
        return 0;
    }
    return 60 / $speed; // minutes per kilometer
}

// Function to format pace in minutes and seconds
function formatPace($paceMinutes)
{
    if ($paceMinutes == 0) {
        return "0:00 min/km";
    }

    $minutes = floor($paceMinutes);
    $seconds = round(($paceMinutes - $minutes) * 60);

    return sprintf("%d:%02d min/km", $minutes, $seconds);
}

// Function to calculate estimated finish time based on current pace
function calculateEstimatedFinishTime($totalDistance, $coveredDistance, $elapsedTime)
{
    if ($coveredDistance <= 0) {
        return 0;
    }

    $currentSpeed = $coveredDistance / $elapsedTime;
    $remainingDistance = $totalDistance - $coveredDistance;
    $remainingTime = $remainingDistance / $currentSpeed;

    return $elapsedTime + $remainingTime;
}

// Function to analyze historical data trends
function analyzeTrends($historicalData)
{
    if (empty($historicalData) || count($historicalData) < 2) {
        return null;
    }

    $trends = [];
    $recentData = array_slice($historicalData, -5); // Last 5 entries

    // Calculate average speeds
    $currentSpeeds = array_column($recentData, 5);
    $requiredSpeeds = array_column($recentData, 6);

    $avgCurrentSpeed = array_sum($currentSpeeds) / count($currentSpeeds);
    $avgRequiredSpeed = array_sum($requiredSpeeds) / count($requiredSpeeds);

    $trends['avg_current_speed'] = $avgCurrentSpeed;
    $trends['avg_required_speed'] = $avgRequiredSpeed;
    $trends['speed_deficit'] = $avgRequiredSpeed - $avgCurrentSpeed;
    $trends['trend_direction'] = $trends['speed_deficit'] > 0 ? 'needs_improvement' : 'on_track';

    return $trends;
}

// Function to generate race statistics
function generateRaceStats($historicalData)
{
    if (empty($historicalData)) {
        return null;
    }

    $stats = [];

    // Basic statistics
    $totalEntries = count($historicalData);
    $currentSpeeds = array_column($historicalData, 5);
    $requiredSpeeds = array_column($historicalData, 6);

    $stats['total_entries'] = $totalEntries;
    $stats['avg_current_speed'] = array_sum($currentSpeeds) / count($currentSpeeds);
    $stats['avg_required_speed'] = array_sum($requiredSpeeds) / count($requiredSpeeds);
    $stats['max_current_speed'] = max($currentSpeeds);
    $stats['min_current_speed'] = min($currentSpeeds);

    // Calculate consistency (standard deviation)
    $variance = array_sum(array_map(function ($x) use ($stats) {
        return pow($x - $stats['avg_current_speed'], 2);
    }, $currentSpeeds)) / count($currentSpeeds);

    $stats['speed_consistency'] = sqrt($variance);

    return $stats;
}

// Function to export data to CSV format
function exportToCSV($data, $filename = 'race_export.csv')
{
    if (empty($data)) {
        return false;
    }

    $headers = [
        'Date/Time',
        'Total Distance',
        'Covered Distance',
        'Elapsed Time',
        'Target Time',
        'Current Speed',
        'Required Speed'
    ];

    $csvContent = implode(',', $headers) . "\n";

    foreach ($data as $row) {
        $csvContent .= implode(',', $row) . "\n";
    }

    return file_put_contents($filename, $csvContent);
}

// Function to calculate split times for different distances
function calculateSplitTimes($totalDistance, $coveredDistance, $elapsedTime, $targetTime)
{
    $splits = [];
    $currentSpeed = $coveredDistance / $elapsedTime;
    $requiredSpeed = ($totalDistance - $coveredDistance) / ($targetTime - $elapsedTime);

    // Calculate splits for every 10km
    for ($distance = 10; $distance <= $totalDistance; $distance += 10) {
        if ($distance <= $coveredDistance) {
            // Already completed this split
            $splitTime = ($distance / $coveredDistance) * $elapsedTime;
        } else {
            // Calculate remaining time for this split
            $remainingDistance = $distance - $coveredDistance;
            $splitTime = $elapsedTime + ($remainingDistance / $requiredSpeed);
        }

        $splits[$distance] = $splitTime;
    }

    return $splits;
}

// Function to format split times
function formatSplitTimes($splits)
{
    $formatted = [];
    foreach ($splits as $distance => $time) {
        $formatted[$distance] = formatTime($time);
    }
    return $formatted;
}

// Function to validate and sanitize file data
function validateFileData($data)
{
    $validated = [];

    foreach ($data as $row) {
        if (count($row) >= 7) {
            // Validate each field
            $timestamp = $row[0];
            $totalDistance = filter_var($row[1], FILTER_VALIDATE_FLOAT);
            $coveredDistance = filter_var($row[2], FILTER_VALIDATE_FLOAT);
            $elapsedTime = filter_var($row[3], FILTER_VALIDATE_FLOAT);
            $targetTime = filter_var($row[4], FILTER_VALIDATE_FLOAT);
            $currentSpeed = filter_var($row[5], FILTER_VALIDATE_FLOAT);
            $requiredSpeed = filter_var($row[6], FILTER_VALIDATE_FLOAT);

            // Only add if all values are valid
            if (
                $totalDistance && $coveredDistance !== false && $elapsedTime !== false &&
                $targetTime && $currentSpeed !== false && $requiredSpeed !== false
            ) {
                $validated[] = $row;
            }
        }
    }

    return $validated;
}

// Function to backup historical data
function backupHistoricalData($sourceFile, $backupFile = null)
{
    if (!file_exists($sourceFile)) {
        return false;
    }

    if ($backupFile === null) {
        $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '_' . basename($sourceFile);
    }

    return copy($sourceFile, $backupFile);
}

// Function to clear old historical data (keep only last N entries)
function cleanupHistoricalData($dataFile, $keepEntries = 100)
{
    if (!file_exists($dataFile)) {
        return false;
    }

    $lines = file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (count($lines) <= $keepEntries) {
        return true; // No cleanup needed
    }

    $recentLines = array_slice($lines, -$keepEntries);
    return file_put_contents($dataFile, implode("\n", $recentLines) . "\n");
}
?>