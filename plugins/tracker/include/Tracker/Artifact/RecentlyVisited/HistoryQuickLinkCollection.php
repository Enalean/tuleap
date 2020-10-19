<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\User\History\HistoryQuickLink;

class HistoryQuickLinkCollection implements Dispatchable
{
    public const NAME = 'getHistoryQuickLinkCollection';

    /**
     * @var HistoryQuickLink[]
     */
    private $links = [];
    /**
     * @var Artifact
     */
    private $artifact;
    /**
     * @var \PFUser
     */
    private $current_user;

    public function __construct(Artifact $artifact, \PFUser $current_user)
    {
        $this->artifact     = $artifact;
        $this->current_user = $current_user;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    public function add(HistoryQuickLink $link): void
    {
        $this->links[] = $link;
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }
}
