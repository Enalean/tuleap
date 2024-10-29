<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Project\Service;

use ParagonIE\EasyDB\EasyStatement;
use Project;
use Tuleap\DB\DataAccessObject;

class ServiceDao extends DataAccessObject
{
    public function searchByProjectAndShortNames(Project $project, array $allowed_shortnames): array
    {
        if (empty($allowed_shortnames)) {
            return [];
        }

        $shortnames = EasyStatement::open()->in('?*', $allowed_shortnames);

        $sql = "SELECT *
                FROM service
                WHERE group_id = ?
                    AND short_name IN ($shortnames)
                ORDER BY `rank`";

        return $this->getDB()->run($sql, $project->getID(), ...$shortnames->values());
    }

    public function isServiceAvailableAtSiteLevelByShortName(string $name): bool
    {
        return $this->isProjectActive(\Project::DEFAULT_TEMPLATE_PROJECT_ID, $name);
    }

    public function isServiceActiveInProjectByShortName(\Project $project, string $name): bool
    {
        return $this->isProjectActive((int) $project->getID(), $name);
    }

    public function updateServiceUsageByShortName(\Project $project, string $short_name, bool $is_used): void
    {
        $this->getDB()->update(
            'service',
            ['is_used'  => $is_used],
            ['group_id' => (int) $project->getID(), 'short_name' => $short_name]
        );
    }

    public function updateServiceUsageByServiceID(\Project $project, int $service_id, bool $is_used): void
    {
        $this->getDB()->update(
            'service',
            ['is_used'             => $is_used],
            ['group_id' => (int) $project->getID(), 'service_id' => $service_id]
        );
    }

    public function delete(int|string $project_id, int $id): void
    {
        $this->getDB()->delete('service', ['group_id' => $project_id, 'service_id' => $id]);
    }

    public function deleteFromAllProjects(string $short_name): void
    {
        $this->getDB()->delete('service', ['short_name' => $short_name]);
    }

    public function create(
        int|string $project_id,
        string $label,
        string $icon_name,
        string $description,
        string $short_name,
        ?string $link,
        bool $is_active,
        bool $is_used,
        string $scope,
        int $rank,
        bool $is_in_new_tab,
    ): int {
        return $this->getDB()->insert(
            'service',
            [
                'group_id'   => (int) $project_id,
                'short_name' => $short_name,
                'label'      => $label,
                'icon'   => $icon_name,
                'description' => $description,
                'is_active'   => $is_active ? 1 : 0,
                'is_used'     => $is_used ? 1 : 0,
                'is_in_new_tab' => $is_in_new_tab ? 1 : 0,
                'is_in_iframe' => 0,
                'link'        => $link,
                'rank'        => $rank,
                'scope'       => $scope,
            ]
        );
    }

    public function saveBasicInformation(
        int $service_id,
        string $label,
        string $icon_name,
        string $description,
        ?string $link,
        int $rank,
        bool $is_in_iframe,
        bool $is_in_new_tab,
    ): void {
        $this->getDB()->update(
            'service',
            [
                'label' => $label,
                'icon' => $icon_name,
                'description' => $description,
                'link' => $link,
                'rank' => $rank,
                'is_in_iframe' => $is_in_iframe ? 1 : 0,
                'is_in_new_tab' => $is_in_new_tab ? 1 : 0,
            ],
            ['service_id' => $service_id]
        );
    }

    public function saveIsActiveAndScope(int $service_id, bool $is_active, string $scope): void
    {
        $this->getDB()->update(
            'service',
            [
                'is_active' => $is_active ? 1 : 0,
                'scope'     => $scope,
            ],
            ['service_id' => $service_id]
        );
    }

    public function searchById(int $id): array
    {
        $sql = 'SELECT * FROM service WHERE service_id = ?';

        return $this->getDB()->row($sql, $id);
    }

    public function getServiceInfoQueryForNewProject(array $legacy, int $template_id): array
    {
        $forbidden_shortname = [];

        foreach ($legacy as $service_shortname => $legacy_service_usage) {
            if (! $legacy_service_usage) {
                $forbidden_shortname[] = $service_shortname;
            }
        }

        if (count($forbidden_shortname) === 0) {
            $sql = 'SELECT * FROM service WHERE group_id=? AND is_active=1';
            return $this->getDB()->run($sql, $template_id);
        }

        $shortnames = EasyStatement::open()->in('?*', $forbidden_shortname);
        $sql        = 'SELECT * FROM service WHERE group_id=? AND is_active=1 AND short_name NOT IN (?)';

        return $this->getDB()->run($sql, $template_id, ...$shortnames->values());
    }

    private function isProjectActive(int $project_id, string $name): bool
    {
        $sql = 'SELECT *
                 FROM service
                 WHERE group_id = ?
                     AND is_active = 1
                     AND short_name = ?
                 LIMIT 1';
        return $this->getDB()->exists($sql, $project_id, $name);
    }
}
