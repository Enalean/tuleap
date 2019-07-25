<?php
/**
  * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

use Tuleap\Admin\AdminPageRenderer;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/admin_utils.php';

$controller = new Admin_Homepage_Controller(
    new CSRFSynchronizerToken($_SERVER['SCRIPT_NAME']),
    new Admin_Homepage_Dao(),
    $request,
    $GLOBALS['Response'],
    new AdminPageRenderer(),
    new ConfigDao()
);
$router = new Admin_Homepage_Router($controller, $request);
$router->route();
