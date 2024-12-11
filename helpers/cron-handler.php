<?php

function parse_cron(string $cron_expression): array {
    $parts = preg_split('/\s+/', $cron_expression);
    if (count($parts) !== 5) {
        throw new Exception("invalid_cron_expression");
    }

    return $parts;
}

function cron_matches(array $cron_parts, int $time): bool {
    [$min, $hour, $day, $month, $weekday] = $cron_parts;

    $current = [
        'min'       => (int) date('i', $time),
        'hour'      => (int) date('G', $time),
        'day'       => (int) date('j', $time),
        'month'     => (int) date('n', $time),
        'weekday'   => (int) date('w', $time),
    ];

    return matches_part($min, $current['min']) &&
        matches_part($hour, $current['hour']) &&
        matches_part($day, $current['day']) &&
        matches_part($month, $current['month']) &&
        matches_part($weekday, $current['weekday']);
}

function matches_part(string $cron_part, int $current_value): bool {
    if($cron_part === '*') {
        return true;
    }

    if(strpos($cron_part, '/') !== false) {
        // Handle step values (e.g., */5)
        [$range, $step] = explode('/', $cron_part);
        $step = (int) $step;
        if($range === '*') {
            return $current_value % $step === 0;
        }
    }

    if(strpos($cron_part, ',') !== false) {
        // Handle lists (e.g., 1,15,30)
        $values = explode(',', $cron_part);
        return in_array($current_value, array_map('intval', $values));
    }

    if(strpos($cron_part, '-') !== false) {
        // Handle ranges (e.g., 1-5)
        list($start, $end) = explode('-', $cron_part);
        return $current_value >= (int) $start && $current_value <= (int) $end;
    }

    // Handle single values (e.g., 15)
    return (int) $cron_part === $current_value;
}

function handle_cron_jobs(array $cron_jobs): array {
    $map_results = [];

    $current_time = time();
    foreach($cron_jobs as $job) {
        if(empty($job['crontab']) || empty($job['controller'])) {
            continue;
        }

        try {
            $cron_parts = parse_cron($job['crontab']);
            if(!cron_matches($cron_parts, $current_time)) {
                continue;
            }

            $controller_file = CONTROLLERS_DIR . '/' . $job['controller'] . '.php';

            // Check if the controller or script file exists
            if(!file_exists($controller_file)) {
                throw new Exception("missing_script_file", 503);
            }

            // Include the controller file
            include_once $controller_file;

            $handler_method_name = preg_replace('/[-\/]/', '_', $job['controller']);

            // Call the controller function with the request data
            if(!is_callable($handler_method_name)) {
                throw new Exception("missing_method", 501);
            }

            load_env(BASE_DIR . '/.env');

            // Respond with the returned body and code
            ['body' => $body, 'code' => $code] = $handler_method_name($job['data'] ?? []);
        }
        catch(Exception $e) {
            [$body, $code] = [$e->getMessage(), $e->getCode()];
        }

        $map_results[$job['controller']] = compact('body', 'code');
    }

    return $map_results;
}
