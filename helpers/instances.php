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

    $directories = array_map('basename', $directories);

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

/**
 * Returns true if the instance is on the server
 *
 * @param string $instance
 * @return bool
 * @throws Exception
 */
function instance_exists(string $instance): bool {
    if(empty($instance) || $instance !== basename($instance)) {
        return false;
    }

    return in_array($instance, get_instances());
}

/**
 * Returns true if the maintenance mode is currently enabled for the given instance
 *
 * @param string $instance
 * @return bool
 */
function instance_is_maintenance_enabled(string $instance): bool {
    return file_exists("/srv/docker/nginx/html/$instance/maintenance");
}

/**
 * Enables maintenance mode for a specific instance
 *
 * @param string $instance
 * @return void
 */
function instance_enable_maintenance_mode(string $instance) {
    if(!instance_is_maintenance_enabled($instance)) {
        file_put_contents("/srv/docker/nginx/html/$instance/maintenance", "");
    }
}

/**
 * Disables maintenance mode for a specific instance
 *
 * @param string $instance
 * @return void
 */
function instance_disable_maintenance_mode(string $instance) {
    if(instance_is_maintenance_enabled($instance)) {
        unlink("/srv/docker/nginx/html/$instance/maintenance");
    }
}
