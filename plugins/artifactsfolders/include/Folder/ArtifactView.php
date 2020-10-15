<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Folder;

use Codendi_Request;
use PFUser;
use TemplateRendererFactory;
use Tracker_Artifact_View_View;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactView extends Tracker_Artifact_View_View
{
    public const IDENTIFIER = 'artifactsfolders';

    /**
     * @var ArtifactPresenterBuilder
     */
    private $artifact_presenter_builder;

    public function __construct(
        Artifact $artifact,
        Codendi_Request $request,
        PFUser $user,
        ArtifactPresenterBuilder $artifact_presenter_builder
    ) {
        parent::__construct($artifact, $request, $user);
        $this->artifact_presenter_builder = $artifact_presenter_builder;
    }

    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle()
    {
        return dgettext('tuleap-artifactsfolders', 'Folder Content');
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(ARTIFACTSFOLDERS_TEMPLATE_DIR);

        return $renderer->renderToString(
            'artifact-tab',
            new Presenter(
                $this->artifact_presenter_builder->buildInFolder($this->user, $this->artifact)
            )
        );
    }
}
