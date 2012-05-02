<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

if ($argc != 2) {
    die("Usage: ".basename($argv[0]).". /path/to/BookInfo.xml
Example:
".basename($argv[0])." documentation/user_guide/xml/en_US/BookInfo.xml
");
}

$bookinfo = simplexml_load_file($argv[1], 'SimpleXMLElement', LIBXML_NOWARNING );

$last_revision = $bookinfo->xpath('/bookinfo/revhistory/revision[last()]');

$rev_number = (string) $last_revision[0]->revnumber;

echo $rev_number.PHP_EOL;
?>
