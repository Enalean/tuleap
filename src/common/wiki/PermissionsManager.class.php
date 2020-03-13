<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\PHPWiki\WikiPage;

/*
 * I extract permissions according wiki page rights, wiki service rights
 * and project visibility.
 *
 * To do this, I ponderate each user groupes regarding their restriction level:
 *
 * Anonymous (1)
 * Registered Users (2)
 * Project members and static groups (3)
 * Project admin (4)
 * Wiki admin (14)
 *
 * Their ponderation points are their ugroup id. For static, I consider them as
 * restrictive as project members.
 *
 * How I do it ?
 *
 * Let's take a look at the following example:
 *
 * Wiki service rights   |   Wiki page rights
 *
 * Project admin             Project members
 * Static ugroup 02          Static ugroup 01
 *
 *
 * For each wiki service rights, we compare with page rights :
 *
 * Project admin (4) > Project members (3), so we remove Project members
 * Project admin (4) > Static ugroup 01 (3), so we remove Static ugroup 01
 *
 * At the end, we can see that wiki service rights are more restricive than wiki page rights,
 * So wee keep wiki service rights.
 *
 */

class Wiki_PermissionsManager
{

    public const WIKI_PERMISSION_READ         = 'WIKIPAGE_READ';
    public const SERVICE_WIKI_PERMISSION_READ = 'WIKI_READ';

    /** @var PermissionsManager */
    private $permission_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var UGroupLiteralizer */
    private $literalizer;

    public function __construct(
        PermissionsManager $permission_manager,
        ProjectManager $project_manager,
        UGroupLiteralizer $literalizer
    ) {
        $this->permission_manager = $permission_manager;
        $this->project_manager    = $project_manager;
        $this->literalizer        = $literalizer;
    }

    public function getFromattedUgroupsThatCanReadWikiPage(WikiPage $wiki_page)
    {
        $project    = $this->project_manager->getProject($wiki_page->getGid());
        $ugroup_ids = $this->permission_manager->getAuthorizedUgroupIds(
            $wiki_page->getId(),
            self::WIKI_PERMISSION_READ
        );

        $ugroup_ids = $this->filterWikiPagePermissionsAccordingToService($project, $ugroup_ids);
        $ugroup_ids = $this->filterWikiPagePermissionsAccordingToProject($project, $ugroup_ids);

        return $this->literalizer->ugroupIdsToString($ugroup_ids, $project);
    }

    private function filterWikiPagePermissionsAccordingToService(Project $project, array $wiki_page_ugroup_ids)
    {
        $wiki_service_ugroup_ids = $this->getWikiServicePermissions($project);

        foreach ($wiki_service_ugroup_ids as $wiki_service_ugroup_id) {
            $this->checkServiceOverridesPagePermission($wiki_page_ugroup_ids, $wiki_service_ugroup_id);
        }

        if (empty($wiki_page_ugroup_ids)) {
            $wiki_page_ugroup_ids = $wiki_service_ugroup_ids;
        }

        return array_merge($wiki_page_ugroup_ids, $this->getWikiAdminsGroups());
    }

    private function checkServiceOverridesPagePermission(array &$wiki_page_ugroup_ids, $wiki_service_ugroup_id)
    {
        foreach ($wiki_page_ugroup_ids as $key => $wiki_page_ugroup_id) {
            $comparable_wiki_page_ugroup_id    = $this->getComparableUGroupId($wiki_page_ugroup_id);
            $comparable_wiki_service_ugroup_id = $this->getComparableUGroupId($wiki_service_ugroup_id);

            if ((int) $comparable_wiki_service_ugroup_id > (int) $comparable_wiki_page_ugroup_id) {
                unset($wiki_page_ugroup_ids[$key]);
            }
        }
    }

    private function filterWikiPagePermissionsAccordingToProject(Project $project, $ugroup_ids)
    {
        if (! $project->isPublic()) {
            $ugroup_ids = array_diff($ugroup_ids, $this->getNonProjectMembersGroups());
        }

        return $ugroup_ids;
    }

    public function getWikiServicePermissions(Project $project)
    {
        return $this->permission_manager->getAuthorizedUgroupIds($project->getID(), self::SERVICE_WIKI_PERMISSION_READ);
    }

    private function getNonProjectMembersGroups()
    {
        return array(ProjectUGroup::REGISTERED, ProjectUGroup::ANONYMOUS);
    }

    /**
     * @return array
     */
    public function getWikiAdminsGroups()
    {
        return array(ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::WIKI_ADMIN);
    }

    /**
     * @param int $comparable_ugroup_id
     *
     * @return int
     */
    private function getComparableUGroupId($comparable_ugroup_id)
    {
        if ($comparable_ugroup_id > 100) {
            return ProjectUGroup::PROJECT_MEMBERS;
        }

        return $comparable_ugroup_id;
    }

    public function isUgroupUsed($ugroup_id, $project_id)
    {
        $project = $this->project_manager->getProject($project_id);
        if (! $project->usesWiki()) {
            return false;
        }

        return $this->permission_manager->isUgroupUsedByWikiService($ugroup_id, $project_id);
    }
}
