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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_ArtifactLinkInfo;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;

class ArtifactLinksToRenderForPerNatureTable
{
    /**
     * @var NaturePresenter
     */
    private $nature_presenter;
    /**
     * @var Tracker_ArtifactLinkInfo[]
     */
    private $artifact_links;

    public function __construct(NaturePresenter $nature_presenter, Tracker_ArtifactLinkInfo ...$artifact_links)
    {
        $this->nature_presenter = $nature_presenter;
        $this->artifact_links   = $artifact_links;
    }

    /**
     * @return NaturePresenter
     */
    public function getNaturePresenter()
    {
        return $this->nature_presenter;
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    public function getArtifactLinks()
    {
        return $this->artifact_links;
    }
}
