<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Reference;

use Tuleap\ServerHostname;

class CrossReferencePresenterFactory
{
    /**
     * @var CrossReferencesDao
     */
    private $dao;

    public function __construct(CrossReferencesDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return CrossReferencePresenter[]
     */
    public function getTargetsOfEntity(string $entity_id, string $entity_type, int $entity_project_id): array
    {
        $targets = [];
        foreach ($this->dao->searchTargetsOfEntity($entity_id, $entity_type, $entity_project_id) as $row) {
            $targets[] = new CrossReferencePresenter(
                (int) $row['id'],
                $row['target_type'],
                $row['target_keyword'] . ' #' . $row['target_id'],
                $this->getUrl($row['target_keyword'], $row['target_id'], (int) $row['target_gid']),
                $this->getDeleteUrl($row),
                (int) $row['target_gid'],
                $row['target_id'],
                null,
                [],
                null,
            );
        }

        return $targets;
    }

    /**
     * @return CrossReferencePresenter[]
     */
    public function getSourcesOfEntity(string $entity_id, string $entity_type, int $entity_project_id): array
    {
        $sources = [];
        foreach ($this->dao->searchSourcesOfEntity($entity_id, $entity_type, $entity_project_id) as $row) {
            $sources[] = new CrossReferencePresenter(
                (int) $row['id'],
                $row['source_type'],
                $row['source_keyword'] . ' #' . $row['source_id'],
                $this->getUrl($row['source_keyword'], $row['source_id'], (int) $row['source_gid']),
                $this->getDeleteUrl($row),
                (int) $row['source_gid'],
                $row['source_id'],
                null,
                [],
                null,
            );
        }

        return $sources;
    }

    private function getUrl(string $keyword, string $id, int $project_id): string
    {
        $parameters = [
            'key' => $keyword,
            'val' => $id,
        ];

        if ($project_id !== \Project::DEFAULT_TEMPLATE_PROJECT_ID) {
            $parameters['group_id'] = $project_id;
        }

        return ServerHostname::HTTPSUrl() . '/goto?' . http_build_query($parameters);
    }

    private function getDeleteUrl(array $row): string
    {
        return '/reference/rmreference.php?' .
            http_build_query(
                [
                    'target_id'   => $row['target_id'],
                    'target_gid'  => $row['target_gid'],
                    'target_type' => $row['target_type'],
                    'target_key'  => $row['target_keyword'],
                    'source_id'   => $row['source_id'],
                    'source_gid'  => $row['source_gid'],
                    'source_type' => $row['source_type'],
                    'source_key'  => $row['source_keyword'],
                ]
            );
    }
}
