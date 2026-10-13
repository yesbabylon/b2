<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Creates a compressed archive containing the Docker images used by an instance stack.
 *
 * The resulting B2 archive contains one `<url-encoded-image-reference>.tar` file
 * per image at its root. Each inner tar file is produced by a separate `docker save`
 * command and can therefore be passed independently to `docker image load`.
 * No additional B2 manifest or index file is added.
 *
 * The exact layout of each inner Docker archive may vary with the Docker version.
 * It normally contains:
 * - `manifest.json`: one entry per image, with its configuration file, repository
 *   tags (`RepoTags`) and ordered filesystem layers (`Layers`);
 * - `<config-id>.json`: the image configuration, including metadata, history and
 *   root filesystem layer references;
 * - `<layer-id>/layer.tar`: the filesystem changes introduced by a layer;
 * - `<layer-id>/json` and `<layer-id>/VERSION`: legacy layer metadata kept for
 *   backward compatibility;
 * - `repositories`, on Docker archive formats that retain the legacy repository/tag
 *   mapping;
 * - `blobs/sha256/*`, `index.json` and `oci-layout` on Docker versions that also
 *   emit an OCI-compatible image layout. Those blobs contain image manifests,
 *   configurations and filesystem layers addressed by their SHA-256 digest.
 *
 * The backup does not include containers, volumes, Docker Compose files, runtime
 * configuration or instance data. Layers shared by multiple images may be repeated
 * in their respective inner archives. Gzip compresses the outer B2 tar archive.
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

    $random_suffix = bin2hex(random_bytes(6));
    $temporary_file = $backup_file.'.tmp.'.$random_suffix;
    $temporary_directory = "$export_directory/.stack-backup-$random_suffix";

    if(!mkdir($temporary_directory, 0700)) {
        throw new Exception('could_not_create_stack_backup_directory', 500);
    }

    $image_archives = [];

    try {
        foreach($images as $image) {
            // Docker references contain path separators and delimiters. URL-encoding
            // keeps the complete reference reversible while producing one safe filename.
            $archive_name = rawurlencode($image).'.tar';
            $archive_file = "$temporary_directory/$archive_name";
            $output = [];
            $exit_code = 0;
            $command = 'docker save --output '.escapeshellarg($archive_file).' '.escapeshellarg($image);

            exec($command.' 2>/dev/null', $output, $exit_code);

            if($exit_code !== 0 || !is_file($archive_file) || filesize($archive_file) === 0) {
                throw new Exception('stack_image_backup_failed', 500);
            }

            $image_archives[] = $archive_name;
        }

        // Store every per-image Docker archive directly at the root of the final
        // compressed tar archive. No stack.json or intermediate directory is added.
        $escaped_archives = implode(' ', array_map('escapeshellarg', $image_archives));
        $command = 'tar -czf '.escapeshellarg($temporary_file)
            .' -C '.escapeshellarg($temporary_directory).' -- '.$escaped_archives;
        $output = [];
        $exit_code = 0;

        exec($command.' 2>/dev/null', $output, $exit_code);

        if($exit_code !== 0 || !is_file($temporary_file) || filesize($temporary_file) === 0) {
            throw new Exception('stack_backup_failed', 500);
        }
    }
    catch(Exception $exception) {
        instance_backup_stack_cleanup_directory($temporary_directory);

        if(file_exists($temporary_file)) {
            unlink($temporary_file);
        }

        throw $exception;
    }

    instance_backup_stack_cleanup_directory($temporary_directory);

    if(!rename($temporary_file, $backup_file)) {
        unlink($temporary_file);
        throw new Exception('could_not_finalize_stack_backup', 500);
    }

    return [
        'code' => 201,
        'body' => [ 'result' => basename($backup_file) ]
    ];
}

/**
 * Removes a temporary stack-backup directory and its per-image tar files.
 *
 * @param string $directory
 * @return void
 */
function instance_backup_stack_cleanup_directory(string $directory): void {
    $files = glob($directory.'/*');

    if(is_array($files)) {
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
    }

    if(is_dir($directory)) {
        rmdir($directory);
    }
}
