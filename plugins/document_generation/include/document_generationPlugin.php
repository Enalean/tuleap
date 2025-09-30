<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

use Tuleap\DocumentGeneration\CrossReport\CrossReportExportPropertiesFetcher;
use Tuleap\DocumentGeneration\Report\ReportCriteriaJsonBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Renderer\Table\GetExportOptionsMenuItemsEvent;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class document_generationPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-document_generation', __DIR__ . '/../site-content');
    }

    #[\Override]
    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\DocumentGeneration\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return ['tracker'];
    }

    #[\Override]
    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(GetExportOptionsMenuItemsEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getExportOptionsMenuItems(GetExportOptionsMenuItemsEvent $event): void
    {
        $current_user = UserManager::instance()->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return;
        }

        $report  = $event->getReport();
        $tracker = $report->getTracker();
        $project = $tracker->getProject();

        if (! $this->_getPluginManager()->isPluginAllowedForProject($this, $project)) {
            return;
        }

        $report_id         = $report->getId();
        $report_name       = $report->getName();
        $tracker_shortname = $tracker->getItemName();

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');

        $report_criteria_json = (new ReportCriteriaJsonBuilder(new UGroupManager()))->buildReportCriteriaJson($report);

        $event->addExportItem(
            $renderer->renderToString(
                'tracker-report-action',
                [
                    'properties' => json_encode(
                        [
                            'report_id' => $report_id,
                            'report_name' => $report_name,
                            'report_has_changed' => $report->hasChanged(),
                            'tracker_shortname' => $tracker_shortname,
                            'platform_name' => ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
                            'platform_logo_url' => Admin_Homepage_LogoFinder::getCurrentUrl(),
                            'project_name' => $project->getPublicName(),
                            'tracker_id' => $tracker->getId(),
                            'tracker_name' => $tracker->getName(),
                            'user_display_name' => UserHelper::instance()->getDisplayNameFromUser($current_user),
                            'user_timezone' => \Tuleap\TimezoneRetriever::getUserTimezone($current_user),
                            'report_url' => \Tuleap\ServerHostname::HTTPSUrl() . '/plugins/tracker/?report=' . urlencode((string) $report_id),
                            'report_criteria' => $report_criteria_json,
                            'base_url' => \Tuleap\ServerHostname::HTTPSUrl(),
                            'artifact_links_types' => (new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao(), new SystemTypePresenterBuilder(EventManager::instance())))->getAllUsableTypesInProject($tracker->getProject()),
                        ],
                        JSON_THROW_ON_ERROR
                    ),
                ]
            )
        );

        $cross_report_properties_fetcher = new CrossReportExportPropertiesFetcher();

        $event->addExportItem(
            $renderer->renderToString(
                'tracker-cross-report-action',
                [
                    'properties' => json_encode(
                        $cross_report_properties_fetcher->fetchExportProperties($tracker, $report, $event->getRendererTable()),
                        JSON_THROW_ON_ERROR
                    ),
                ]
            )
        );

        $document_generation_asset = new IncludeViteAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/document_generation'
        );
        $event->addJavascriptAssets(
            new JavascriptViteAsset($document_generation_asset, 'scripts/index.ts')
        );
    }
}
