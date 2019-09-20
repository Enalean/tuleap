<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2010
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * any later version.
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

/**
 * This script extract the version of a package from the sources.
 */

require_once 'FakePluginDescriptor.php';

$fpd  = new FakePluginDescriptor(dirname(__FILE__).'/../..');
$desc = $fpd->getDescriptor($argv[1]);

// Show version
echo $desc->getVersion().PHP_EOL;

// Show Desc
if (isset($argv[2])) {
    echo $desc->getDescription().PHP_EOL;
}
