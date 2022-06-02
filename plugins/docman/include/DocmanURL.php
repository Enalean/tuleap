<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

use Tuleap\Event\Dispatchable;

final class DocmanURL implements Dispatchable
{
    public const NAME = 'docmanURL';

    /**
     * @readonly
     */
    public \Project $project;

    private string $url;

    public function __construct(\Project $project)
    {
        $this->project = $project;
        $this->url     = DOCMAN_BASE_URL . "?group_id=" . $project->getID();
    }

    public function setServiceUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getServiceURL(): string
    {
        return $this->url;
    }
}
