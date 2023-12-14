<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore;

use EventManager;
use Project;
use Project_AccessException;
use ProjectUGroup;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\CachedProjectAccessChecker;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\UGroupRetriever;
use UGroupManager;

final class SvnAccessFileDefaultBlockGenerator implements SvnAccessFileDefaultBlockGeneratorInterface
{
    private static ?self $instance;
    /**
     * @var array<int, SvnAccessFileDefaultBlock>
     */
    private array $project_default_blocks_cache = [];

    public function __construct(private readonly UGroupRetriever $ugroup_retriever, private readonly CheckProjectAccess $check_project_access, private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self(
                new UGroupManager(),
                new CachedProjectAccessChecker(
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    )
                ),
                EventManager::instance(),
            );
        }
        return self::$instance;
    }

    public function getDefaultBlock(Project $project): SvnAccessFileDefaultBlock
    {
        $project_id = (int) $project->getID();
        if (! isset($this->project_default_blocks_cache[$project_id])) {
            $default_block_plugin_override                   = $this->getDefaultBlockPluginOverride($project);
            $this->project_default_blocks_cache[$project_id] = new SvnAccessFileDefaultBlock(
                $this->getSVNAccessGroups($project, $default_block_plugin_override) . "\n" . $this->getSVNAccessRootPathDef($project, $default_block_plugin_override)
            );
        }

        return $this->project_default_blocks_cache[$project_id];
    }

    private function getDefaultBlockPluginOverride(Project $project): SVNAccessFileDefaultBlockOverride
    {
        $ugroups = [];
        foreach ($this->ugroup_retriever->getUgroups($project) as $ugroup) {
            if ($ugroup->getId() > ProjectUGroup::DYNAMIC_UPPER_BOUNDARY || $ugroup->getId() === ProjectUGroup::PROJECT_MEMBERS) {
                $ugroups[$ugroup->getId()] = $ugroup;
            }
        }
        return $this->dispatcher->dispatch(new SVNAccessFileDefaultBlockOverride($project, ...$ugroups));
    }

    private function getSVNAccessGroups(Project $project, SVNAccessFileDefaultBlockOverride $default_block_plugin_override): string
    {
        $ugroup_list = '';
        foreach ($default_block_plugin_override->getSVNUserGroups() as $svn_group) {
            $ugroup_list .= sprintf("%s = %s\n", $svn_group->name, implode(', ', $this->getAllowedUserNamesFromSVNUserList($project, $svn_group->users)));
        }

        return <<<EOT
        [groups]
        $ugroup_list
        EOT;
    }

    /**
     * @psalm-param SVNUser[] $svn_user_list
     * @return string[]
     */
    private function getAllowedUserNamesFromSVNUserList(Project $project, array $svn_user_list): array
    {
        $allowed = [];
        foreach ($svn_user_list as $svn_user) {
            try {
                $this->check_project_access->checkUserCanAccessProject($svn_user->user, $project);
                $allowed[] = $svn_user->svn_username;
            } catch (Project_AccessException) {
                // do not add user that cannot access project
            }
        }
        return $allowed;
    }

    private function getSVNAccessRootPathDef(Project $project, SVNAccessFileDefaultBlockOverride $default_block_plugin_override): string
    {
        if ($default_block_plugin_override->isWorldAccessForbidden()) {
            $world_access = '* =';
        } elseif ($project->isPublic()) {
            $world_access = '* = r';
        } else {
            $world_access = '* =';
        }

        $members_access = '@' . SVNUserGroup::MEMBERS . ' = rw';

        return <<<EOT
        [/]
        $world_access
        $members_access

        EOT;
    }
}
