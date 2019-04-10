<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Document\Tree;

use HTTPRequest;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class DocumentTreeUnderConstructionController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var DocumentTreeProjectExtractor
     */
    private $project_extractor;

    public function __construct(DocumentTreeProjectExtractor $project_extractor)
    {
        $this->project_extractor = $project_extractor;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        $user = $request->getCurrentUser();
        $user->setPreference("plugin_document_set_display_under_construction_modal_" . $project->getID(), true);

        $GLOBALS['HTML']->redirect("/plugins/document/" . $variables['project_name'] . "/" . $variables['vue-routing']);
    }

    /**
     * @param array       $variables
     *
     * @throws NotFoundException
     */
    public function getProject(array $variables) : Project
    {
        return $this->project_extractor->getProject($variables);
    }
}
