<?php

/**
 * Returns host status statistics.
 * body.config.ip_* can be false if interfaces names are not ens3 (protected), veth0 (public) and ens4 (private).
 *
 * }
 * @throws Exception
 */
function backup(): array {
    $result = [];
    
    $instances = get_instances();

    foreach($instances as $instance) {
        $output = exec("/usr/bin/php ".BASE_DIR."/src/run.php --route=instance/backup --instance=$instance");
        $result[$instance] = json_decode($output, true);
    }

    return [
        'code' => 200,
        'body' => $result
    ];
}
