<?php
 /*
    This file is part of the B2 package <http://github.com/yesbabylon/b2>
    Some Rights Reserved, Yesbabylon, 2025
    Licensed under MIT License <https://opensource.org/licenses/MIT>
*/

/**
 * Returns the Docker images installed on the host as repository:tag strings.
 *
 * @return string[]
 * @throws Exception
 */
function get_images(): array {
    $output = [];
    $exit_code = 0;

    exec("docker image ls --format '{{.Repository}}:{{.Tag}}' 2>/dev/null", $output, $exit_code);

    if($exit_code !== 0) {
        throw new Exception('could_not_list_docker_images', 500);
    }

    $images = array_filter(array_map('trim', $output));

    return array_values(array_unique($images));
}
