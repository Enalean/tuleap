<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\SVNCore;

use Project;
use SVN_AccessFile_Writer;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;

/**
 * Manage the edition of .SVNAccessFile
 *
 * When updating .SVNAccessFile this class verifies ugroup permission lines.
 * It comments lines with invalid syntax, non-existent or empty ugroups.
 */
class SVNAccessFile
{
    /**
     * Value in $groups when the group is (re)defined by user
     */
    public const UGROUP_REDEFINED = 0;

    /**
     * Value in $groups when the group is defined only in default [groups] section
     */
    public const UGROUP_DEFAULT = 1;

    /**
     * Pattern used to find a line defining permission on group
     */
    public const GROUPNAME_PATTERN = "([a-zA-Z0-9_-]+)";

    /**
     * New name of the renamed group
     */
    private ?string $ugroupNewName = null;

    /**
     * Old name of the renamed group
     */
    private ?string $ugroupOldName = null;

    private string $platformBlock = '';

    /**
     * Detect if a line is correctly formatted and corresponds to a defined group
     *
     * @psalm-return Option<Fault>
     */
    public function isGroupDefined(array $groups, string $line): Option
    {
        preg_match($this->getGroupMatcher(self::GROUPNAME_PATTERN), $line, $matches);
        if (! empty($matches)) {
            $match = $matches[1];
            if ($match == 'members') {
                return Option::nothing(Fault::class);
            } else {
                foreach ($groups as $group => $value) {
                    if ($group == $match) {
                        return Option::nothing(Fault::class);
                    }

                    if (strtolower($group) === strtolower($match)) {
                        return Option::fromValue(Fault::fromMessage(sprintf(_('Be careful, "%s" does not match the case sensitivity. Its rule has been disabled.'), $group)));
                    }
                }

                return Option::fromValue(Fault::fromMessage(sprintf(_('User group "%s" is empty or does not exist'), $match)));
            }
        }

        return Option::fromValue(Fault::fromMessage(sprintf(_('Invalid line "%s"'), $line)));
    }

    /**
     * Update renamed ugroup line or comment invalid ugroup line.
     * This validation process cover all groups defined until the current line.
     */
    public function validateUGroupLine(array $groups, string $line, CollectionOfSVNAccessFileFaults $faults): string
    {
        $trimmedLine = ltrim($line);
        if (! empty($this->ugroupNewName) && $this->ugroupOldName !== null && preg_match($this->getGroupMatcher($this->ugroupOldName), $trimmedLine)) {
            return $this->renameGroup($groups, $line);
        } else {
            return $this->commentInvalidLine($groups, $line, $faults);
        }
    }

    /**
     * If line contains a mention of renamed group, update it
     */
    public function renameGroup(array $groups, string $line): string
    {
        if ($this->ugroupOldName === null || $this->ugroupNewName === null) {
            return $line;
        }
        foreach ($groups as $group => $value) {
            //Only groups defined in the default section and not have been redefined in the extra one should be renamed
            if ($group == $this->ugroupOldName && $value == self::UGROUP_REDEFINED) {
                return $line;
            }
        }

        $ugroup_name_to_rename = $this->ugroupOldName;
        if (str_contains($line, strtolower($this->ugroupOldName))) {
            $ugroup_name_to_rename = strtolower($this->ugroupOldName);
        }

        return str_replace($ugroup_name_to_rename, $this->ugroupNewName, $line);
    }

    /**
     * Comments the line corresponding to groups that are not defined
     */
    public function commentInvalidLine(array $groups, string $line, CollectionOfSVNAccessFileFaults $faults): string
    {
        $trimmedLine = ltrim($line);
        if (str_starts_with($trimmedLine, '@')) {
            return $this->isGroupDefined($groups, $trimmedLine)->mapOr(
                function (Fault $fault) use ($line, $faults) {
                    $faults->add($fault);
                    return '# ' . $line;
                },
                $line,
            );
        }
        return $line;
    }

    /**
     * Update renamed ugroup line or comment invalid ugroup lines for all lines of .SVNAccessFile
     */
    public function parseGroupLines(Project $project, string $contents): SVNAccessFileContentAndFaults
    {
        $faults       = new CollectionOfSVNAccessFileFaults();
        $new_contents =  $this->parseGroup($project->getSVNRootPath(), $contents, $faults);
        return new SVNAccessFileContentAndFaults($new_contents, $faults);
    }

    public function parseGroupLinesByRepositories(string $svn_dir, string $contents): SVNAccessFileContentAndFaults
    {
        $faults       = new CollectionOfSVNAccessFileFaults();
        $new_contents = $this->parseGroup($svn_dir, $contents, $faults);
        return new SVNAccessFileContentAndFaults($new_contents, $faults);
    }

    private function parseGroup(string $svn_dir, string $contents, CollectionOfSVNAccessFileFaults $faults): string
    {
        $defaultLines   = explode("\n", $this->getPlatformBlock($svn_dir));
        $groups         = [];
        $currentSection = -1;
        foreach ($defaultLines as $line) {
            $currentSection = $this->getCurrentSection($line, $currentSection);
            if ($currentSection == 'groups') {
                $groups = $this->accumulateDefinedGroups($groups, $line, true);
            }
        }
        $lines         = explode("\n", $contents);
        $validContents = '';
        foreach ($lines as $line) {
            $currentSection = $this->getCurrentSection($line, $currentSection);
            switch ($currentSection) {
                case 'groups':
                    $groups         = $this->accumulateDefinedGroups($groups, $line, false);
                    $validContents .= $line . PHP_EOL;
                    break;
                default:
                    $validContents .= $this->validateUGroupLine($groups, $line, $faults) . PHP_EOL;
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
     * @param array $groups Groups accumulated until the current line
     * @param String $line Current line
     * @param bool $defaultSection Distinguish list of groups retrieved from default [groups] section
     * and those retrieved from extra [groups] section.
     */
    public function accumulateDefinedGroups(array $groups, string $line, bool $defaultSection = false): array
    {
        $trimmedLine = ltrim($line);
        if ($trimmedLine != '') {
            preg_match('/^' . self::GROUPNAME_PATTERN . '\s*=/', $trimmedLine, $matches);
            if (! empty($matches)) {
                if (! $defaultSection) {
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
     */
    public function getCurrentSection(string $line, string|int $currentSection): string|int
    {
        $trimmedLine = ltrim($line);
        if (strcasecmp(substr($trimmedLine, 0, 8), '[groups]') === 0) {
            $currentSection = 'groups';
        } elseif (substr($trimmedLine, 0, 1) == '[') {
            $currentSection = -1;
        }
        return $currentSection;
    }

    /**
     * Set the group to rename
     */
    public function setRenamedGroup(?string $ugroupNewName, ?string $ugroupOldName): void
    {
        $this->ugroupNewName = $ugroupNewName;
        $this->ugroupOldName = $ugroupOldName;
    }

    /**
     * Match the pattern of a line defining permission on a group
     */
    protected function getGroupMatcher(string $groupPattern): string
    {
        return '/^@' . $groupPattern . '\s*=/i';
    }

    /**
     * Returns permissions defined by Tuleap (based on ugroups)
     */
    protected function getPlatformBlock(string $project_svnroot): string
    {
        if (! $this->platformBlock) {
            $accessfile          = new SVN_AccessFile_Writer($project_svnroot);
            $this->platformBlock = $accessfile->read_defaults(true);
        }
        return $this->platformBlock;
    }

    /**
     * Define the platform default groups & root perms
     */
    public function setPlatformBlock(string $block): void
    {
        $this->platformBlock = $block;
    }
}
