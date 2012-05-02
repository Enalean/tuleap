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
 */
class SVNAccessFile {

    /**
     * Value in $groups when the group is (re)defined by user
     */
    const UGROUP_REDEFINED = 0;

    /**
     * Value in $groups when the group is defined only in default [groups] section
     */
    const UGROUP_DEFAULT   = 1;

    /**
     * Pattern used to find a line defining permission on group
     */
    const GROUPNAME_PATTERN = "([a-zA-Z0-9_-]+)";

    /**
     * New name of the renamed group
     *
     * @var String
     */
    private $ugroupNewName = null;

    /**
     * Old name of the renamed group
     *
     * @var String
     */
    private $ugroupOldName = null;
    
    private $platformBlock = '';
    
    /**
     * Detect if a line is correctly formatted and
     * corresponds to a defined group
     *
     * @param Array   $groups  List of already defined groups
     * @param String  $line    Line to validate
     * @param Boolean $verbose Show feedback or not
     *
     * @return Boolean
     */
    public function isGroupDefined($groups, $line, $verbose = false) {
        preg_match($this->getGroupMatcher(self::GROUPNAME_PATTERN), $line, $matches);
        if (!empty($matches)) {
            $match = strtolower($matches[1]);
            if ($match == 'members') {
                return true;
            } else {
                foreach ($groups as $group => $value) {
                    if ($group == $match) {
                        return true;
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
     * Update renamed ugroup line or comment invalid ugroup line.
     * This validation process cover all groups defined until the current line.
     *
     * @param Array   $groups  List of already defined groups
     * @param String  $line    Line to validate
     * @param Boolean $verbose Show feedback or not
     *
     * @return String
     */
    public function validateUGroupLine($groups, $line, $verbose = null) {
        $trimmedLine = ltrim($line);
        if (!empty($this->ugroupNewName) && preg_match($this->getGroupMatcher($this->ugroupOldName), $trimmedLine)) {
            return $this->renameGroup($groups, $line);
        } else {
            return $this->commentInvalidLine($groups, $line, $verbose);
        }
    }

    /**
     * If line contains a mention of renamed group, update it
     *
     * @param Array  $groups Defined groups
     * @param String $line   Line that may be renamed
     *
     * @return String
     */
    public function renameGroup($groups, $line) {
        $line = strtolower($line);
        $renamable = true;
        foreach ($groups as $group => $value) {
            //Only groups defined in the default section and not have been redefined in the extra one should be renamed
            if ($group == $this->ugroupOldName) {
                if ($value == self::UGROUP_REDEFINED) {
                    $renamable = false;
                    return $line;
                }
            }
        }
        if ($renamable) {
            return str_replace($this->ugroupOldName, $this->ugroupNewName, $line);
        }
    }

    /**
     * Comments the line corresponding to groups that are not defined
     * 
     * @param Array   $groups  List of already defined groups
     * @param String  $line    Line to validate
     * @param Boolean $verbose Show feedback or not
     * 
     * @return String
     */
    public function commentInvalidLine($groups, $line, $verbose = false) {
        $trimmedLine = ltrim($line);
        if ($trimmedLine && substr($trimmedLine, 0, 1) == '@' && !$this->isGroupDefined($groups, $trimmedLine, $verbose)) {
            return "# ".$line;
        } else {
            return $line;
        }
    }

    /**
     * Update renamed ugroup line or comment invalid ugroup lines for all lines of .SVNAccessFile
     *
     * @param Project $project  Project of the svn repository
     * @param String  $contents Text to validate
     * @param Boolean $verbose  Show feedback or not
     *
     * @return String
     */
    public function parseGroupLines($project, $contents, $verbose = false) {
        $defaultLines = explode("\n", $this->getPlatformBlock($project->getUnixName(false)));
        $groups = array();
        $currentSection = -1;
        foreach ($defaultLines as $line) {
            $currentSection = $this->getCurrentSection($line, $currentSection);
            if ($currentSection == 'groups') {
                $groups = $this->accumulateDefinedGroups($groups, $line, true);
            }
        }
        $lines = explode("\n", $contents);
        $validContents = '';
        foreach ($lines as $line) {
            $currentSection = $this->getCurrentSection($line, $currentSection);
            switch ($currentSection) {
                case 'groups' :
                    $groups = $this->accumulateDefinedGroups($groups, $line, false);
                    $validContents .= $line.PHP_EOL;
                    break;
                default :
                    $validContents .= $this->validateUGroupLine($groups, $line, $verbose).PHP_EOL;
                    break;
            }
        }
        return substr($validContents, 0, -1);
    }

    /**
     * Get the list of groups defined until the current line.
     * Foreach $groups as $group => $value :
     * If $value == self::UGROUP_DEFAULT then $group is defined only in the default [groups] section (it's a regular ugroup).
     * If $value == self::UGROUP_REDEFINED then $group is a regular ugroup that has been redefined in an extra [groups] section.
     *
     * @param Array   $groups         Groups accumulated until the current line
     * @param String  $line           Current line
     * @param Boolean $defaultSection Distinguish list of groups retrieved from default [groups] section
     *                                and those retrieved from extra [groups] section.
     *
     * @return Array
     */
    public function accumulateDefinedGroups($groups, $line, $defaultSection = false) {
        $trimmedLine = ltrim(strtolower($line));
        if ($trimmedLine != '') {
            preg_match('/^'.self::GROUPNAME_PATTERN.'\s*=/', $trimmedLine, $matches);
            if (!empty($matches)) {
                if (!$defaultSection){
                    $groups[$matches[1]] = self::UGROUP_REDEFINED;
                } else {
                    $groups[$matches[1]] = self::UGROUP_DEFAULT;
                }
            }
        }
        return $groups;
    }

    /**
     * For the moment it just tells if the current section is [groups] or not
     *
     * @param String $line           Current line
     * @param String $currentSection Current section d'oh!
     *
     * @return String
     */
    public function getCurrentSection($line, $currentSection) {
        $trimmedLine = ltrim(strtolower($line));
        if (substr($trimmedLine, 0, 8) == '[groups]') {
            $currentSection = 'groups';
        } elseif (substr($trimmedLine, 0, 1) == '[') {
            $currentSection = -1;
        }
        return $currentSection;
    }

    /**
     * Set the group to rename
     *
     * @param String $ugroupNewName New group name
     * @param String $ugroupOldName Old group name
     *
     * @return void
     */
    public function setRenamedGroup($ugroupNewName, $ugroupOldName) {
        $this->ugroupNewName = strtolower($ugroupNewName);
        $this->ugroupOldName = strtolower($ugroupOldName);
    }

    /**
     * Match the pattern of a line difining permission on a gorup
     *
     * @param String $groupPattern Pattern to match group name
     *
     * @return String
     */
    protected function getGroupMatcher($groupPattern) {
        return '/^@'.$groupPattern.'\s*=/';
    }

    /**
     * Returns permissions defined by Tuleap (based on ugroups)
     *
     * @param String $projectName
     *
     * @return String
     */
    protected function getPlatformBlock($projectName) {
        if (!$this->platformBlock) {
            $this->platformBlock = svn_utils_read_svn_access_file_defaults($projectName, true);
        }
        return $this->platformBlock;
    }
    
    /**
     * Define the platform default groups & root perms
     *
     * @param String $block Default SVN block
     */
    public function setPlatformBlock($block) {
        $this->platformBlock = $block;
    }
}

?>