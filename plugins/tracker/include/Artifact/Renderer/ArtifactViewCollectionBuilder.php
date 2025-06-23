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
use ForgeConfig;
use PFUser;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\View\ArtifactViewEdit;
use Tuleap\Tracker\Artifact\View\LinksView;
use Tuleap\Tracker\Artifact\View\TypeView;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;

final class ArtifactViewCollectionBuilder
{
    #[FeatureFlagConfigKey('In artifact add the tab `Children` again')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG_KEY = 'reactivate_tab_children_in_artifact_view';

    public function __construct(private EventManager $event_manager, private TypeIsChildLinkRetriever $retriever)
    {
    }

    public function build(Artifact $artifact, \Tuleap\Tracker\Tracker $tracker, Codendi_Request $request, PFUser $user, \Tracker_Artifact_EditRenderer $renderer): ViewCollection
    {
        $view_collection = new ViewCollection($this->event_manager);
        $view_collection->add(new ArtifactViewEdit($artifact, $request, $user, $renderer));

        if ((int) ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY) === 1) {
            if ($artifact->getTracker()->isProjectAllowedToUseType()) {
                $artifact_links = $this->retriever->getChildren($artifact);
                if (count($artifact_links) > 0) {
                    $view_collection->add(new TypeView($artifact, $request, $user));
                }
            } else {
                if ($artifact->getTracker()->getChildren()) {
                    $view_collection->add(new TypeView($artifact, $request, $user));
                }
            }
        }

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
