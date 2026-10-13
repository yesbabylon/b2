<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Adds the missing Docker images from an imported instance stack backup.
 *
 * @param array{instance: string, backup_id: string} $data
 * @return array{code: int, body: array{result: string, integrated: string[], skipped: string[]}}
 * @throws Exception
 */
function instance_integrate_backup_stack(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException('missing_instance', 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException('invalid_instance', 400);
    }

    if(!isset($data['backup_id'])) {
        throw new InvalidArgumentException('missing_backup_id', 400);
    }

    if(!is_string($data['backup_id']) || preg_match('/^\d{13}$/', $data['backup_id']) !== 1) {
        throw new InvalidArgumentException('invalid_backup_id', 400);
    }

    $instance = $data['instance'];
    $backup_file = "/home/$instance/import/{$instance}_{$data['backup_id']}_stack.tar.gz";

    if(!is_file($backup_file) || !is_readable($backup_file)) {
        throw new Exception('backup_not_found', 404);
    }

    $image_archives = instance_integrate_backup_stack_archives($backup_file);
    $installed_images = get_images();
    $integrated = [];
    $skipped = [];

    foreach(array_keys($image_archives) as $image) {
        if(in_array($image, $installed_images, true)) {
            $skipped[] = $image;
        }
        else {
            $integrated[] = $image;
        }
    }

    if(!empty($integrated)) {
        foreach($integrated as $reference) {
            $archive_name = $image_archives[$reference];
            $pipeline = 'set -o pipefail; tar -xOzf '.escapeshellarg($backup_file)
                .' -- '.escapeshellarg($archive_name).' | docker image load';
            $output = [];
            $exit_code = 0;

            exec('bash -c '.escapeshellarg($pipeline).' 2>/dev/null', $output, $exit_code);

            if($exit_code !== 0) {
                throw new Exception('stack_integration_failed', 500);
            }
        }

        $installed_images = get_images();

        foreach($integrated as $reference) {
            if(!in_array($reference, $installed_images, true)) {
                throw new Exception('stack_integration_failed', 500);
            }
        }
    }

    sort($integrated, SORT_STRING);
    sort($skipped, SORT_STRING);

    return [
        'code' => 200,
        'body' => [
            'result' => 'backup_stack_integrated',
            'integrated' => $integrated,
            'skipped' => $skipped
        ]
    ];
}

/**
 * Lists the per-image Docker archives contained in a B2 stack backup.
 *
 * The outer `.tar.gz` has no manifest of its own. Every root entry must be a Docker
 * archive named `<rawurlencoded-image-reference>.tar`. The decoded reference is
 * normalized with Docker's implicit `latest` tag before comparison with `get_images()`.
 * Keeping one Docker archive per reference allows the controller to stream only
 * missing images to `docker image load`.
 *
 * @param string $backup_file
 * @return array<string, string> Map of image references to archive member names.
 * @throws Exception
 */
function instance_integrate_backup_stack_archives(string $backup_file): array {
    $output = [];
    $exit_code = 0;
    $command = 'tar -tzf '.escapeshellarg($backup_file).' 2>/dev/null';

    exec($command, $output, $exit_code);

    if($exit_code !== 0 || empty($output)) {
        throw new Exception('invalid_stack_backup', 422);
    }

    $image_archives = [];

    foreach($output as $archive_name) {
        if(!is_string($archive_name)
            || preg_match('/^(.+)\.tar$/D', $archive_name, $matches) !== 1
        ) {
            throw new Exception('invalid_stack_backup', 422);
        }

        $encoded_reference = $matches[1];
        $decoded_reference = rawurldecode($encoded_reference);

        // Re-encoding enforces the canonical filename format produced by
        // backup-stack.php and rejects paths, malformed escapes and extra entries.
        if($decoded_reference === '' || rawurlencode($decoded_reference) !== $encoded_reference) {
            throw new Exception('invalid_stack_backup', 422);
        }

        $reference = instance_integrate_backup_stack_normalize_reference($decoded_reference);

        if(isset($image_archives[$reference])) {
            throw new Exception('invalid_stack_backup', 422);
        }

        $image_archives[$reference] = $archive_name;
    }

    return $image_archives;
}

/**
 * Adds Docker's implicit `latest` tag so references match get_images() output.
 * A colon in the registry host (for example `registry:5000/image`) is not a tag.
 *
 * @param string $reference
 * @return string
 */
function instance_integrate_backup_stack_normalize_reference(string $reference): string {
    if(strpos($reference, '@') !== false) {
        return $reference;
    }

    $last_slash = strrpos($reference, '/');
    $last_colon = strrpos($reference, ':');

    if($last_colon === false || ($last_slash !== false && $last_colon < $last_slash)) {
        return $reference.':latest';
    }

    return $reference;
}
