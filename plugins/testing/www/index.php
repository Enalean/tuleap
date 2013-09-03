<?php

/*
 * Copyright (c) Xerox, 2013. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2013. Xerox Codendi Team.
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

//
// front-end to plugin testing//

require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');

$plugin_manager = PluginManager::instance();
$p = $plugin_manager->getPluginByName('testing');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $p->process(HTTPRequest::instance());
} else {
    header('Location: '.get_server_url());
}