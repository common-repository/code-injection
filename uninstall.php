<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('ci_code_injection_db_version');
delete_option('ci_code_injection_role_version');