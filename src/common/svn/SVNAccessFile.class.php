<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

/**
 * Manage the edition of .SVNAccessFile
 *
 * When updating .SVNAccessFile this class verifies ugroup permission lines.
 * It comments lines with invalid synatx, non existant or empty ugroups.
 * If a such line is kept uncommented it will cause error when browsing
 * svn tree and will make svn requests fail with a 403 (forbidden) error
 */
class SVNAccessFile {

    /**
     * Detect if a line is correctly formatted and 
     * corresponds to a valid ugroup which is not empty
     *
     * @param Project $project Project of the svn repository
     * @param String  $line    Line to validate
     * @param Boolean $verbose Show feedback or not
     *
     * @return Boolean
     */
    function isValidUGroupLine($project, $line, $verbose = false) {
        preg_match('/^@([a-zA-Z0-9\-_]+)[ ]*=/', $line, $matches);
        if (!empty($matches)) {
            $match = $matches[1];
            if ($match == 'members') {
                return true;
            } else {
                $ugroupDao = $this->_getUGroupDao();
                $dar = $ugroupDao->searchByGroupId($project->getId());
                foreach ($dar as $row) {
                    $ugroup = $this->_getUGroupFromRow($row);
                    if ($ugroup->getName() == $match) {
                        $members = $ugroup->getMembers();
                        if (empty($members)) {
                            if ($verbose) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('svn_admin_access_control', 'ugroup_empty', $match));
                            }
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
                if ($verbose) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('svn_admin_access_control', 'no_ugroup', $match));
                }
                return false;
            }
        }
        if ($verbose) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('svn_admin_access_control', 'invalid_line', $line));
        }
        return false;
    }

    /**
     * Update renamed ugroup line or comment invalid ugroup line
     *
     * @param Project $project         Project of the svn repository
     * @param String  $line            Line to validate
     * @param Boolean $verbose         Show feedback or not
     * @param String  $ugroup_name     New name of the renamed ugroup
     * @param String  $ugroup_old_name Old name of the renamed ugroup
     *
     * @return String
     */
    function validateUGroupLine($project, $line, $verbose = null, $ugroup_name = null, $ugroup_old_name = null) {
        $trimmedLine = ltrim($line);
        if (!empty($ugroup_name) && preg_match('/^@'.$ugroup_old_name.'\s*=/', $trimmedLine)) {
            return str_replace($ugroup_old_name, $ugroup_name, $line);
        } elseif ($trimmedLine && substr($trimmedLine, 0, 1) == '@' && !$this->isValidUGroupLine($project, $trimmedLine, $verbose)) {
            return "# ".$line;
        } else {
            return $line;
        }
    }

    /**
     * Comment invalid ugroup lines
     *
     * @param Project $project  Project of the svn repository
     * @param String  $contents Text to validate
     * @param Boolean $verbose  Show feedback or not
     *
     * @return String
     */
    function validateUGroupLines($project, $contents, $verbose = false) {
        $lines = split("[\n]", $contents);
        $validContents = '';
        foreach ($lines as $line) {
            $validContents .= $this->validateUGroupLine($project, $line, $verbose).PHP_EOL;
        }
        return substr($validContents, 0, -1);
    }

    /**
     * Wrapper for tests
     *
     * @return UGroupDao
     */
    protected function _getUGroupDao() {
        return new UGroupDao(CodendiDataAccess::instance());
    }

    /**
     * Wrapper for tests
     *
     * @param array $row a row from the db for a ugroup
     *
     * @return UGroup
     */
    protected function _getUGroupFromRow($row) {
        return new UGroup($row);
    }

}

?>