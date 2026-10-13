<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns a map of instance names to images used by their Docker Compose containers.
 * Stopped containers are included, and instances without containers have an empty list.
 *
 * @return array<string, string[]>
 * @throws Exception
 */
function get_stacks(): array {
    $instances = get_instances();
    $stacks = array_fill_keys($instances, []);
    $output = [];
    $exit_code = 0;
    $command = 'docker ps -a --no-trunc --filter "label=com.docker.compose.project" '
        .'--format \'{{.Label "com.docker.compose.project.working_dir"}}|{{.Image}}\' 2>/dev/null';

    exec($command, $output, $exit_code);

    if($exit_code !== 0) {
        throw new Exception('could_not_list_docker_stacks', 500);
    }

    foreach($output as $line) {
        $parts = explode('|', trim($line), 2);
        if(count($parts) !== 2) {
            continue;
        }

        [$working_directory, $image] = $parts;
        $instance = basename($working_directory);

        if($image !== '' && array_key_exists($instance, $stacks)) {
            $stacks[$instance][] = $image;
        }
    }

    foreach($stacks as &$images) {
        $images = array_values(array_unique($images));
        sort($images, SORT_STRING);
    }
    unset($images);

    return $stacks;
}
