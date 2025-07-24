<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Renderer;

use Codendi_Request;
use EventManager;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\View\ArtifactViewEdit;
use Tuleap\Tracker\Artifact\View\LinksView;

final class ArtifactViewCollectionBuilder
{
    public function __construct(private EventManager $event_manager)
    {
    }

    public function build(Artifact $artifact, Codendi_Request $request, PFUser $user, \Tracker_Artifact_EditRenderer $renderer): ViewCollection
    {
        $view_collection = new ViewCollection($this->event_manager);
        $view_collection->add(new ArtifactViewEdit($artifact, $request, $user, $renderer));
        $view_collection->add(new LinksView($artifact, $request, $user));

        $this->event_manager->processEvent(
            \Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION,
            [
                'artifact'   => $artifact,
                'collection' => $view_collection,
                'request'    => $request,
                'user'       => $user,
            ]
        );

        return $view_collection;
    }
}
