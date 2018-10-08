<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tracker_Artifact;
use Tuleap\Event\Dispatchable;

class AdditionalArtifactActionButtonsFetcher implements Dispatchable
{
    const NAME = "additionalArtifactActionButtonsFetcher";

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @var AdditionalButtonLinkPresenter[]
     */
    private $additional_links = [];

    public function __construct(Tracker_Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    public function getArtifact()
    {
        return $this->artifact;
    }

    public function addLinkPresenter(AdditionalButtonLinkPresenter $link)
    {
        $this->additional_links[] = $link;
    }

    /**
     * @returns AdditionalButtonLinkPresenter[]
     */
    public function getAdditionalLinks()
    {
        return $this->additional_links;
    }
}
