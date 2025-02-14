<?php

/**
 * Convert an ISO 8601 duration (e.g., "PT4M13S") to total seconds.
 */

use Illuminate\Http\JsonResponse;

/**
 * This is for Converting String based Duration to seconds.
 * Usual inputted format: PT2M39S
 * Output is second.
 * @param string $duration
 * @return float|int
 */
function convertDurationToSeconds(string $duration): float|int
{
    if (!$duration) {
        return 0;
    }

    // Typical format: "PT4M13S" or "PT1H2M5S"
    preg_match_all('/(\d+)([HMS])/', $duration, $matches, PREG_SET_ORDER);

    $seconds = 0;
    foreach ($matches as $match) {
        $value = (int)$match[1];
        $unit = $match[2];

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
/**
 * This is for returning a catched Error as a JSON
 * @param Exception $e
 * @param int $errorCode
 * @return JsonResponse
 */
function returnErrorJSON(Exception $e, int $errorCode = 500) : JSONResponse
{
    return response()->json([
        'error' => $e->getMessage(),
    ], $errorCode);
}

/**
 * This is for entry returning as a JSON and also handling the empty array.
 * @param $entries
 * @param string $emptyMessage
 * @return JsonResponse
 */
function returnEntriesAsJSON($entries, string $emptyMessage): JsonResponse
{
    if(empty($entries)) {
        return response()->json([
            'empty'   => true,
            'message' => $emptyMessage,
        ]);
    }
    return response()->json([
        'empty'   => false,
        'entries' => $entries,
    ]);
}

/**
 * This is for returning an error message only in JSON format.
 * @param $message
 * @param int $code
 * @return JsonResponse
 */
function returnJSONErrorMessage($message, int $code = 500): JsonResponse
{
    return response()->json([
        'error' => $message,
    ], $code);
}
