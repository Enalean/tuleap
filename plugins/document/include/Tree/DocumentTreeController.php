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
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Docman\FilenamePattern\FilenamePatternRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\Settings\ITellIfWritersAreAllowedToUpdatePropertiesOrDelete;
use Tuleap\Document\Config\ModalDisplayer;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Document\Tree\Create\NewItemAlternativeCollector;
use Tuleap\Document\Tree\Search\ListOfSearchColumnDefinitionPresenterBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class DocumentTreeController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private DocumentTreeProjectExtractor $project_extractor,
        private \DocmanPluginInfo $docman_plugin_info,
        private FileDownloadLimitsBuilder $file_download_limits_builder,
        private ModalDisplayer $modal_display_handler,
        private FilenamePatternRetriever $filename_pattern_retriever,
        private ProjectFlagsBuilder $project_flags_builder,
        private \Docman_ItemDao $dao,
        private ListOfSearchCriterionPresenterBuilder $criteria_builder,
        private ListOfSearchColumnDefinitionPresenterBuilder $column_builder,
        private ITellIfWritersAreAllowedToUpdatePropertiesOrDelete $forbid_writers_settings,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('document');

        $project = $this->getProject($variables);

        $is_item_status_used       = $this->isHardcodedMetadataUsed($project, 'status');
        $is_obsolescence_date_used = $this->isHardcodedMetadataUsed($project, 'obsolescence_date');

        $this->includeCssFiles($layout);
        $this->includeHeaderAndNavigationBar($layout, $project);
        $this->includeJavascriptFiles($layout, $request);

        $root_id = (int) $this->dao->searchRootIdForGroupId($project->getID());
        if (! $root_id) {
            throw new NotFoundException(dgettext('tuleap-document', 'Unable to find the root folder of project'));
        }

        $forbid_writers_to_update = ! $this->forbid_writers_settings->areWritersAllowedToUpdateProperties((int) $project->getID());
        $forbid_writers_to_delete = ! $this->forbid_writers_settings->areWritersAllowedToDelete((int) $project->getID());

        $renderer         = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
        $metadata_factory = new \Docman_MetadataFactory($project->getID());
        $renderer->renderToPage(
            'document-tree',
            new DocumentTreePresenter(
                $project,
                $root_id,
                $request->getCurrentUser(),
                (bool) $this->docman_plugin_info->getPropertyValueForName('embedded_are_allowed'),
                $is_item_status_used,
                $is_obsolescence_date_used,
                (bool) $this->docman_plugin_info->getPropertyValueForName('only_siteadmin_can_delete'),
                $forbid_writers_to_update,
                $forbid_writers_to_delete,
                new CSRFSynchronizerToken('plugin-document'),
                $this->file_download_limits_builder->build(),
                $this->modal_display_handler->isChangelogModalDisplayedAfterDragAndDrop((int) $project->getID()),
                $this->project_flags_builder->buildProjectFlags($project),
                $this->criteria_builder->getSelectedCriteria(
                    $metadata_factory,
                    new ItemStatusMapper(new \Docman_SettingsBo($project->getID())),
                    $project
                ),
                $this->column_builder->getColumns($project, $metadata_factory),
                $this->filename_pattern_retriever->getPattern((int) $project->getID()),
                $this->dispatcher->dispatch(new ShouldDisplaySourceColumnForFileVersions())->shouldDisplaySourceColumn(),
                $this->dispatcher->dispatch(new NewItemAlternativeCollector($project))->getSections(),
            )
        );

        $layout->footer(FooterConfiguration::withoutContent());
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
            __DIR__ . '/../../frontend-assets',
            '/assets/document'
        );
    }

    private function includeJavascriptFiles(BaseLayout $layout, HTTPRequest $request): void
    {
        $core_assets = new \Tuleap\Layout\IncludeCoreAssets();
        $layout->includeFooterJavascriptFile($core_assets->getFileURL('ckeditor.js'));
        $layout->includeFooterJavascriptFile($this->getAssets()->getFileURL('document.js'));
        $layout->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());
    }

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project)
    {
        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-document', "Document manager"))
                ->inProjectNotInBreadcrumbs($project, \DocmanPlugin::SERVICE_SHORTNAME)
                ->withMainClass(['document-main'])
                ->build()
        );
    }

    private function includeCssFiles(BaseLayout $layout)
    {
        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons(
                $this->getAssets(),
                'document-style'
            )
        );
    }
}
