<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Config\Project;

use HTTPRequest;
use Tuleap\Docman\View\Admin\AdminView;
use Tuleap\Document\Tree\IExtractProjectFromVariables;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class SearchView extends AdminView implements DispatchableWithBurningParrot, DispatchableWithProject, DispatchableWithRequest
{
    public const IDENTIFIER = 'admin-search';

    public function __construct(
        private IExtractProjectFromVariables $project_extractor,
        private SearchColumnFilter $column_filter,
        private SearchCriteriaFilter $criteria_filter,
    ) {
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-document', 'Search');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-document', 'Configure search interface');
    }

    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getTitle(array $params): string
    {
        return dgettext('tuleap-document', 'Configure search interface');
    }

    /**
     *
     * @throws NotFoundException
     */
    public function getProject(array $variables): \Project
    {
        return $this->project_extractor->getProject($variables);
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('document');

        $project     = $this->getProject($variables);
        $user        = $request->getCurrentUser();
        $default_url = '/plugins/docman/?group_id=' . urlencode((string) $project->getID());

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/admin-search-view/frontend-assets',
                    '/assets/document/admin-search-view'
                ),
                'src/main.ts'
            )
        );

        $this->displayForProject(
            $project,
            $user,
            $default_url,
            [],
            function () use ($project) {
                $metadata_factory = new \Docman_MetadataFactory($project->getID());

                $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__);
                $renderer->renderToPage('search-view', [
                    'post_url'            => self::getUrl($project),
                    'csrf_token'          => self::getCSRF($project),
                    'criteria'            => $this->criteria_filter->getCriteria($project, $metadata_factory),
                    'columns'             => $this->column_filter->getColumns($project, $metadata_factory),
                    'id_label'            => dgettext('tuleap-document', 'Id'),
                    'title_label'         => dgettext('tuleap-document', 'Title'),
                    'global_search_label' => dgettext('tuleap-document', 'Global search'),
                ]);
            }
        );
    }

    public static function getUrl(\Project $project): string
    {
        return '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/admin-search';
    }

    public static function getCSRF(\Project $project): \CSRFSynchronizerToken
    {
        return new \CSRFSynchronizerToken(self::getUrl($project));
    }
}
