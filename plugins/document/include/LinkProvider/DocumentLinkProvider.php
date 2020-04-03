<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Document\LinkProvider;

use Project;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;

class DocumentLinkProvider implements ILinkUrlProvider
{
    /**
     * @var string
     */
    private $base_url;
    /**
     * @var Project
     */
    private $project;

    public function __construct(string $base_url, Project $project)
    {
        $this->base_url = $base_url;
        $this->project  = $project;
    }

    public function getPluginLinkUrl(): string
    {
        return $this->base_url . "/plugins/docman/?group_id=" . urlencode((string) $this->project->getID());
    }

    public function getShowLinkUrl(\Docman_Item $item): string
    {
        return $this->base_url . "/plugins/document/" .
            urlencode($this->project->getUnixNameLowerCase()) . "/preview/" . urlencode((string) $item->getId());
    }

    public function getDetailsLinkUrl(\Docman_Item $item): string
    {
        return $this->base_url . "/plugins/document/" .
            urlencode($this->project->getUnixNameLowerCase()) . "/preview/" . urlencode((string) $item->getId());
    }

    public function getNotificationLinkUrl(\Docman_Item $item): string
    {
        return $this->base_url . "/plugins/docman/?group_id=" . urlencode((string) $this->project->getID()) .
            "&action=details&section=notifications&id=" . urlencode((string) $item->getId());
    }

    public function getHistoryUrl(\Docman_Item $item): string
    {
        return $this->base_url . "/plugins/docman/?group_id=" . urlencode((string) $this->project->getID()) .
            "&action=details&section=history&id=" . urlencode((string) $item->getId());
    }
}
