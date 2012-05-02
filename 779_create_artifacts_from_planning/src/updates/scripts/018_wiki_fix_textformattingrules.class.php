<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'CodendiUpgrade.class.php';

/**
 * Edit all TextFormattingRules (resp. RèglesDeFormatageDesTextes in french) 
 * wiki page to remove occurence of those string in "Synopsis" section.
 * We want to remove it because:
 * - TextFormattingRules is a wiki page name so it appears as a link
 * - This page is special because it appears in the edit section of every page
 * - As TextFormattingRules (the page name) appears as a link (because of CamelCase wiki links)
 *   users are tempted to click on it to see those rules. Unforatunatly, as it's a a "straight"
 *   link, the current page is reloaded and the user loose it's work.
 * To avoid this issue, we remove the CamelCase link in the synopsis section and there is no longer
 * bad links to click for users.
 * Note: TextFormattingRules link is still available but with a target="_blank" link.
 */
class Update_018 extends CodendiUpgrade {


    /**
     * Browse the whole wiki database, looking for a pagename (either TextFormattingRules
     * or RèglesDeFormatageDesTextes. Takes the lastest version of the page and if
     * the string "TextFormattingRules" appears in Synopsis section (and equivalent in
     * french), create a new version of the page without it.
     */
    function fixTextFormattingRules($pagename, $pattern) {
        // Get the most recent page version with given name
        $sql = 'SELECT v1.*, wiki_page.pagename, wiki_page.group_id'.
               ' FROM wiki_page'.
               '  JOIN wiki_version v1 ON (v1.id = wiki_page.id)'.
               '  LEFT JOIN wiki_version v2'.
               '   ON (v1.id = v2.id AND v2.version > v1.version)'.
               ' WHERE pagename="'.$pagename.'"'.
               '  AND v2.version IS NULL';
        $dar = $this->retrieve($sql);
        foreach($dar as $row) {
            $count = 0;
            // Try to replace the pattern
            $content = preg_replace($pattern, '$1', $row['content'], -1, $count);
            if ($count == 1) {
                // The page content is modified by the regexp so we need to create a
                // new version of the page.
                echo "Update ".$row['pagename'].' in '.$row['group_id'];

                // Takes version data and change the author by admin because it's
                // admin who modify the page
                $versionData = unserialize($row['versiondata']);
                $versionData['author'] = 'admin';

                // Create new version of the page
                $newVersion = $row['version']+1;
                $sql = 'INSERT INTO wiki_version(id, version, mtime, minor_edit, content, versiondata)'.
                       ' VALUES ('.$row['id'].', '.$newVersion.', '.$_SERVER['REQUEST_TIME'].', 0, '.$this->da->quoteSmart($content).', '.$this->da->quoteSmart(serialize($versionData)).')';
                $this->update($sql);
                if ($this->da->affectedRows() == 1) {
                    echo ' Done'.$this->getLineSeparator();

                    // Update most recent version
                    $sql = 'UPDATE wiki_recent SET latestversion='.$newVersion.', latestmajor='.$newVersion.' where id = '.$row['id'];
                    $this->update($sql);

                    // Clear HTML cache
                    $sql = 'UPDATE wiki_page SET cached_html="" WHERE id = '.$row['id'];
                    $this->update($sql);
                } else {
                    echo ' Failed'.$this->getLineSeparator();
                    $this->addUpgradeError("An error occured when trying to add a new version of ".$row['pagename']." in ".$row['group_id'].": ".$this->da->isError());
                }
            }
        }
    }

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        $this->fixTextFormattingRules('TextFormattingRules', '/(! Synopsis\n)TextFormattingRules%%%\n/');
        $this->fixTextFormattingRules('RèglesDeFormatageDesTextes', '/(! Vue d\'ensemble\n)RèglesDeFormatageDesTextes%%%\n/');

        echo $this->getLineSeparator();
    }
}

?>
