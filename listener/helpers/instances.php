<?php

/**
 * Returns instances depending on /home sub directories
 *
 * @param bool $with_deleted
 * @return array|false
 * @throws Exception
 */
function get_instances(bool $with_deleted = false) {
    $directories_to_ignore = ['ubuntu', 'docker'];

    $directories = glob('/home/*', GLOB_ONLYDIR);
    if($directories === false) {
        throw new Exception("could_not_read_home_directory", 500);
    }

    $directories = array_filter($directories, function($dir) use($directories_to_ignore) {
        return !in_array(basename($dir), $directories_to_ignore);
    });

    if(!$with_deleted) {
        $directories = array_filter($directories, function($dir) {
            return strpos($dir, '_deleted') === false;
        });
    }

    return array_values($directories);
}
