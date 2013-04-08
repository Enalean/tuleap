<?php

/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'pre.php';

//
// front-end to plugin Git//

// hack to make sure that pseudo-nice urls don't bypass the restricted user check
if ( preg_match_all('/^\/plugins\/git\/index.php\/(\d+)\/([^\/][a-zA-Z]+)\/([a-zA-Z\-\_0-9]+)\/\?{0,1}.*/', $_SERVER['REQUEST_URI'], $matches) ) {
    $_REQUEST['group_id'] = $_GET['group_id'] = $matches[1][0];
}


$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('git');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}



?>