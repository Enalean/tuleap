<?php
/*
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../../../src/vendor/autoload.php';

if ($files = glob('*.xml')) {
    echo '<ul>';
    foreach ($files as $filename) {
        $xml_security = new XML_Security();
        $xml = $xml_security->loadFile($filename);
        echo '<li><p>';
        echo '<strong><a href="'. $filename .'">'. $xml->name .'</a></strong><br/>';
        echo ''. $xml->description .'</p>';
        echo '</li>';
    }
    echo '</ul>';
}
?>
