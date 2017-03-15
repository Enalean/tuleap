<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

function get_markdown_path() {
    $potential_paths = array(
        '/usr/share/php-markdown',
        '/usr/share/php', // php55 from remi repo has a different php path
    );

    foreach($potential_paths as $path) {
        $path .= '/Michelf/';
        if (is_dir($path)) {
            return $path;
        }
    }
}

require_once('/usr/share/php/Zend/Loader/StandardAutoloader.php');
$loader = new Zend\Loader\StandardAutoloader(
    array(
        'autoregister_zf' => true,
        'namespaces' => array(
            'Firebase\JWT' => '/usr/share/php-jwt/',
            'Michelf'      => get_markdown_path()
        )
    )
);
$loader->register();

/**
 * Function called when a class is not defined.
 *
 * Used to load Zend classes on the fly
 *
 * @param String $className
 *
 * @return void
 */
function autoload_zend($className) {
    global $Language;
    if (strpos($className, 'Zend') === 0 && !class_exists($className)) {
        if (isset($GLOBALS['zend_path'])) {
            ini_set('include_path', $GLOBALS['zend_path'].':'.ini_get('include_path'));
            $path = str_replace('_', '/', $className);
            require_once $path.'.php';
        } else if (is_dir('/usr/share/zend')) {
            ini_set('include_path', '/usr/share/zend/:'.ini_get('include_path'));
            $path = str_replace('_', '/', $className);
            require_once $path.'.php';
        } else {
            exit_error($Language->getText('global', 'error'), $Language->getText('include_pre', 'zend_path_not_set', $GLOBALS['sys_email_admin']));
        }
    }
}
spl_autoload_register('autoload_zend');

// Load PHP compatibility libraries
require_once('common/include/compat/hash_equals.php');

if (file_exists('/usr/share/php/php-amqplib/autoload.php')) {
    require_once '/usr/share/php/php-amqplib/autoload.php';
}
