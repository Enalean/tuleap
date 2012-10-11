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

require_once 'Section.class.php';

class Release {

    public $version;
    public $date;
    public $sections;

    public function __construct($version, $date) {
        $this->version  = $version;
        $this->date     = $date;
        $this->sections = array();
    }

    /**
     * @return Release
     */
    public static function buildFromChangeLog($tuleap_version, $line) {
        preg_match('/\((.*)\)/', $line, $matches);
        $klass = __CLASS__;
        return new $klass($tuleap_version, $matches[1]);
    }

    /**
     * @return Section
     */
    public function addSectionFromChangeLog($line) {
        $section = Section::buildFromChangeLog($line);
        $this->sections[] = $section;
        return $section;
    }
}
?>
