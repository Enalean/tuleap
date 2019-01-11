<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class DocumentTreeController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
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
        \Tuleap\Project\ServiceInstrumentation::increment('document');

        $project = $this->getProject($request, $variables);

        $user = $request->getCurrentUser();
        $user->setPreference("plugin_docman_display_legacy_ui_" . $project->getID(), false);

        $this->includeCssFiles($layout);
        $this->includeHeaderAndNavigationBar($layout, $project);
        $this->includeJavascriptFiles($layout);

        $preference = $user->getPreference('plugin_document_set_display_under_construction_modal_' . $project->getID());

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
        $renderer->renderToPage(
            'document-tree',
            new DocumentTreePresenter(
                $project,
                $request->getCurrentUser(),
                $preference === '1'
            )
        );

        $layout->footer(["without_content" => true]);
    }

    /**
     * @param HTTPRequest $request
     * @param array       $variables
     *
     * @return \Project
     * @throws NotFoundException
     */
    public function getProject(\HTTPRequest $request, array $variables)
    {
        return $this->project_extractor->getProject($request, $variables);
    }

    /**
     * @return IncludeAssets
     */
    private function includeJavascriptFiles(BaseLayout $layout)
    {
        $include_assets  = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/document/scripts',
            '/assets/document/scripts'
        );

        $layout->includeFooterJavascriptFile($include_assets->getFileURL('document.js'));
    }

    /**
     * @param BaseLayout $layout
     * @param            $project
     */
    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project)
    {
        $layout->header(
            [
                'title'        => dgettext('tuleap-document', "Document manager"),
                'group'        => $project->getID(),
                'toptab'       => 'docman',
                'main_classes' => ['document-main']
            ]
        );
    }

    private function includeCssFiles(BaseLayout $layout)
    {
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../../src/www/assets/document/BurningParrot',
                    '/assets/document/BurningParrot'
                ),
                'document'
            )
        );
    }
}
