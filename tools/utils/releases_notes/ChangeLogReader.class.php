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

require_once 'Release.class.php';
require_once 'Section.class.php';

class ChangeLogReader {

    private $tuleap_version;

    public function __construct($version = null) {
        $this->tuleap_version = $version ? $version : trim(file_get_contents('VERSION'));
    }

    /**
     * @return Release
     */
    public function parse() {
        $changelog   = file('ChangeLog', FILE_IGNORE_NEW_LINES);
        $in_plugins  = false;
        $section     = null;
        $release     = null;
        foreach ($changelog as $line) {
            if (strpos($line, 'Version ') === 0) {
                if ($section) {
                    break;
                }
                $release = Release::buildFromChangeLog($this->tuleap_version, $line);
            } elseif (preg_match('/^\s*== /', $line)) {
                $section = $release->addSectionFromChangeLog($line);
            } else {
                if ($section && $line) {
                    if ($section->hasSubSections() && $line) {
                        $sub_section = $section->addSectionFromChangeLog($line);
                        $this->extractChangelogOfPlugin($sub_section);
                    } else {
                        $section->addChange($line);
                    }
                }
            }
        }
        return $release;
    }

    function extractChangelogOfPlugin($section) {
        $release_notes = array();
        $changelog = file('plugins/'. $section->label .'/ChangeLog', FILE_IGNORE_NEW_LINES);

        foreach ($changelog as $line) {
            if (strpos($line, 'Version ') === 0) {
                if (!preg_match('/Tuleap '. $this->tuleap_version .'\s*$/i', $line)) {
                    break;
                }
            } elseif ($line) {
                $section->addChange($line);
            }
        }
    }
}
?>
