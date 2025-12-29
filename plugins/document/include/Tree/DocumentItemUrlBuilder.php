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

use Tuleap\Project\ProjectByIDFactory;

readonly class DocumentItemUrlBuilder
{
    public function __construct(private ProjectByIDFactory $project_manager)
    {
    }

    public function getUrl(\Docman_Item $item): string
    {
        $base_url = $this->getBaseUrl($item);

        return $item->getParentId() === 0
            ? $base_url
            : $base_url . '/preview/' . urlencode((string) $item->getId());
    }

    public function getRedirectionForEmbeddedFile(\Docman_Item $item): string
    {
        return $this->getBaseUrl($item) . '/folder/' . urlencode((string) $item->getParentId()) . '/' . urlencode((string) $item->getId());
    }

    private function getBaseUrl(\Docman_Item $item): string
    {
        $project = $this->project_manager->getProjectById((int) $item->getGroupId());

        return '/plugins/document/' . urlencode($project->getUnixNameLowerCase());
    }

    public function getRedirectionForFolder(\Docman_Folder $item): string
    {
        return $this->getBaseUrl($item) . '/folder/' . urlencode((string) $item->getId());
    }
}
