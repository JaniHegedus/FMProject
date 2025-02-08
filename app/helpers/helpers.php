<?php

/**
 * Convert an ISO 8601 duration (e.g., "PT4M13S") to total seconds.
 */
function convertDurationToSeconds($duration): float|int
{
    if (!$duration) {
        return 0;
    }

    // Typical format: "PT4M13S" or "PT1H2M5S"
    preg_match_all('/(\d+)([HMS])/', $duration, $matches, PREG_SET_ORDER);

    $seconds = 0;
    foreach ($matches as $match) {
        $value = (int) $match[1];
        $unit  = $match[2];

        if ($unit === 'H') {
            $seconds += $value * 3600;
        } elseif ($unit === 'M') {
            $seconds += $value * 60;
        } elseif ($unit === 'S') {
            $seconds += $value;
        }
    }

    return $seconds;
}
