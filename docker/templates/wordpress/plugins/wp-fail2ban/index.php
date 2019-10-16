<?php
/**
 * Plugin Name: wp-fail2ban 
 * Description: Adds a hook to force HTTP 403 status when login fails and enable fail2ban on WP logs
 * Version: 1.0
 * Author: Cedric Francoys
 * License: GPLv3
 */
/* Return 403 instead of 200 when wp-login failed */
add_action( 'wp_login_failed', function () {
    status_header(403);
} );