<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Creates a compressed archive containing the Docker images used by an instance stack.
 *
 * @param array{instance: string} $data
 * @return array{code: int, body: array{result: string}}
 * @throws Exception
 */
function instance_backup_stack(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException('missing_instance', 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException('invalid_instance', 400);
    }

    $instance = $data['instance'];
    $stacks = get_stacks();
    $images = $stacks[$instance] ?? [];

    if(empty($images)) {
        throw new Exception('stack_images_not_found', 404);
    }

    $export_directory = "/home/$instance/export";
    if(!is_dir($export_directory) || !is_writable($export_directory)) {
        throw new Exception('export_directory_not_writable', 500);
    }

    $timestamp = date('Ymd').sprintf('%05d', time() - strtotime('today'));
    $backup_file = "$export_directory/{$instance}_{$timestamp}_stack.tar.gz";

    $temporary_file = $backup_file.'.tmp.'.bin2hex(random_bytes(6));
    $escaped_images = implode(' ', array_map('escapeshellarg', $images));
    $pipeline = 'set -o pipefail; docker save '.$escaped_images.' | gzip > '.escapeshellarg($temporary_file);
    $output = [];
    $exit_code = 0;

    exec('bash -c '.escapeshellarg($pipeline).' 2>/dev/null', $output, $exit_code);

    if($exit_code !== 0 || !is_file($temporary_file) || filesize($temporary_file) === 0) {
        if(file_exists($temporary_file)) {
            unlink($temporary_file);
        }
        throw new Exception('stack_backup_failed', 500);
    }

    if(!rename($temporary_file, $backup_file)) {
        unlink($temporary_file);
        throw new Exception('could_not_finalize_stack_backup', 500);
    }

    return [
        'code' => 201,
        'body' => [ 'result' => basename($backup_file) ]
    ];
}
