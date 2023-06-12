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
use Tuleap\QuickLink\SwitchToQuickLink;

class SwitchToLinksCollection implements Dispatchable
{
    public const NAME = 'getSwitchToQuickLinkCollection';

    /**
     * @var SwitchToQuickLink[]
     */
    private array $quick_links = [];
    private string $icon_name;
    private string $main_uri;
    private string $xref;

    public function __construct(private Artifact $artifact, private \PFUser $current_user)
    {
        $this->xref      = $this->artifact->getXRef();
        $this->main_uri  = $this->getArtifactUri();
        $this->icon_name = $this->getArtifactIconName();
    }

    public function getQuickLinks(): array
    {
        return $this->quick_links;
    }

    public function addQuickLink(SwitchToQuickLink $link): void
    {
        $this->quick_links[] = $link;
    }

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }

    public function getIconName(): string
    {
        return $this->icon_name;
    }

    public function setIconName(string $icon_name): void
    {
        $this->icon_name = $icon_name;
    }

    public function getMainUri(): string
    {
        return $this->main_uri;
    }

    public function getArtifactUri(): string
    {
        return $this->artifact->getUri();
    }

    public function getArtifactIconName(): string
    {
        return 'fa-solid fa-tlp-tracker';
    }

    public function setMainUri(string $main_uri): void
    {
        $this->main_uri = $main_uri;
    }

    public function getXRef(): string
    {
        return $this->xref;
    }
}
