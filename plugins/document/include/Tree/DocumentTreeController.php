<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use CSRFSynchronizerToken;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
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
    /**
     * @var \DocmanPluginInfo
     */
    private $docman_plugin_info;
    /**
     * @var FileDownloadLimitsBuilder
     */
    private $file_download_limits_builder;

    /**
     * @var HistoryEnforcementSettingsBuilder
     */
    private $history_enforcement_settings_builder;
    /**
     * @var ProjectFlagsBuilder
     */
    private $project_flags_builder;

    public function __construct(
        DocumentTreeProjectExtractor $project_extractor,
        \DocmanPluginInfo $docman_plugin_info,
        FileDownloadLimitsBuilder $file_download_limits_builder,
        HistoryEnforcementSettingsBuilder $history_enforcement_settings_builder,
        ProjectFlagsBuilder $project_flags_builder
    ) {
        $this->project_extractor                    = $project_extractor;
        $this->docman_plugin_info                   = $docman_plugin_info;
        $this->file_download_limits_builder         = $file_download_limits_builder;
        $this->history_enforcement_settings_builder = $history_enforcement_settings_builder;
        $this->project_flags_builder                = $project_flags_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('document');

        $project = $this->getProject($variables);

        $is_item_status_used       = $this->isHardcodedMetadataUsed($project, 'status');
        $is_obsolescence_date_used = $this->isHardcodedMetadataUsed($project, 'obsolescence_date');

        $user = $request->getCurrentUser();
        $user->setPreference("plugin_docman_display_new_ui_" . $project->getID(), '1');

        $this->includeCssFiles($layout);
        $this->includeHeaderAndNavigationBar($layout, $project);
        $this->includeJavascriptFiles($layout, $request);

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
        $renderer->renderToPage(
            'document-tree',
            new DocumentTreePresenter(
                $project,
                $request->getCurrentUser(),
                (bool) $this->docman_plugin_info->getPropertyValueForName('embedded_are_allowed'),
                $is_item_status_used,
                $is_obsolescence_date_used,
                (bool) $this->docman_plugin_info->getPropertyValueForName('only_siteadmin_can_delete'),
                new CSRFSynchronizerToken('plugin-document'),
                $this->file_download_limits_builder->build(),
                $this->history_enforcement_settings_builder->build(),
                $this->project_flags_builder->buildProjectFlags($project),
            )
        );

        $layout->footer(["without_content" => true]);
    }

    /**
     * @param array $variables
     *
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        return $this->project_extractor->getProject($variables);
    }

    private function isHardcodedMetadataUsed(Project $project, string $label): bool
    {
        $docman_setting_bo = new \Docman_SettingsBo($project->getID());

        return $docman_setting_bo->getMetadataUsage($label) === "1";
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/document',
            '/assets/document'
        );
    }

    private function includeJavascriptFiles(BaseLayout $layout, HTTPRequest $request): void
    {
        $core_assets = new IncludeAssets(__DIR__ . '/../../../../src/www/assets/core', '/assets/core');
        $layout->includeFooterJavascriptFile($core_assets->getFileURL('ckeditor.js'));
        $layout->includeFooterJavascriptFile($this->getAssets()->getFileURL('document.js'));
        $layout->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());
    }

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project)
    {
        $layout->header(
            [
                'title'                          => dgettext('tuleap-document', "Document manager"),
                'group'                          => $project->getID(),
                'toptab'                         => 'docman',
                'main_classes'                   => ['document-main'],
                'without-project-in-breadcrumbs' => true,
            ]
        );
    }

    private function includeCssFiles(BaseLayout $layout)
    {
        $layout->addCssAsset(
            new CssAsset(
                $this->getAssets(),
                'style'
            )
        );
    }
}
