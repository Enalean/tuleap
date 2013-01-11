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

class Section {

    public $label;
    public $version;
    public $changes;
    public $sections;

    public function __construct($label, $version) {
        $this->label    = $label;
        $this->version  = $version;
        $this->changes  = array();
        $this->sections = array();
    }

    /**
     * @return Section
     */
    public static function buildFromChangeLog($line) {
        if (preg_match('/^\s*\* (.*):\s*(.*)/', $line, $matches)) {
            $label   = $matches[1];
            $version = $matches[2];
        } else {
            $label   = trim(str_replace('==', '', $line));
            $version = '';
        }
        $klass = __CLASS__;
        return new $klass($label, $version);
    }

    /**
     * @return bool
     */
    public function hasSubSections() {
        return strtolower($this->label) == 'plugins';
    }

    /**
     * @return Section
     */
    public function addSectionFromChangeLog($line) {
        $section = Section::buildFromChangeLog($line);
        $this->addSection($section);
        return $section;
    }

    public function addSection(Section $section) {
        $this->sections[] = $section;
        usort($this->sections, array($this, 'sortByLabel'));
    }

    private function sortByLabel($a, $b) {
        return strnatcasecmp($a->label, $b->label);
    }

    public function addChange($change) {
        if (preg_match('/^\s*\*/', $change)) {
            $this->changes[] = preg_replace('/^\s*\*\s*/', '', $change);
        } else {
            // concatenate with a previous line
            $this->changes[count($this->changes) - 1] .= PHP_EOL . $change;
        }
    }
}
?>
