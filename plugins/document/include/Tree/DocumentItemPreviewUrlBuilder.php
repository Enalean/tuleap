<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

class DocumentItemPreviewUrlBuilder
{
    private function __construct(private \ProjectManager $project_manager)
    {
    }

    public static function buildSelf(): self
    {
        return new self(\ProjectManager::instance());
    }

    public function getUrl(\Docman_Item $item): string
    {
        $project = $this->project_manager->getProject((int) $item->getGroupId());

        $base_url = "/plugins/document/" . urlencode($project->getUnixNameLowerCase());

        return $item->getParentId() === 0
            ? $base_url
            : $base_url . "/preview/" . urlencode((string) $item->getId());
    }
}
