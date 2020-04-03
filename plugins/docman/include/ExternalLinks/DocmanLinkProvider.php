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

namespace Tuleap\Docman\ExternalLinks;

use Tuleap\Event\Dispatchable;

class DocmanLinkProvider implements Dispatchable
{
    public const NAME = "docmanLinkProvider";
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var ILinkUrlProvider
     */
    private $provider;

    public function __construct(\Project $project, ILinkUrlProvider $link_provider)
    {
        $this->project  = $project;
        $this->provider = $link_provider;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function replaceProvider(ILinkUrlProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getProvider(): ILinkUrlProvider
    {
        return $this->provider;
    }
}
