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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Roadmap;

use Codendi_Request;
use Project;
use TemplateRenderer;
use TrackerFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Project\MappingRegistry;
use Tuleap\Roadmap\Widget\PreferencePresenter;
use Tuleap\Roadmap\Widget\RoadmapWidgetPresenterBuilder;

final class RoadmapProjectWidget extends \Widget
{
    public const ID = 'plugin_roadmap_project_widget';

    public const DEFAULT_TIMESCALE_MONTH = 'month';

    private ?int $lvl1_iteration_tracker_id = null;
    private ?int $lvl2_iteration_tracker_id = null;
    private string $default_timescale       = self::DEFAULT_TIMESCALE_MONTH;

    /**
     * @var ?int[]
     */
    private $tracker_ids;
    /**
     * @var ?string
     */
    private $title;

    public function __construct(
        Project $project,
        private readonly RoadmapWidgetDao $dao,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly TemplateRenderer $renderer,
        private readonly RoadmapWidgetPresenterBuilder $presenter_builder,
        private TrackerFactory $tracker_factory,
        private readonly FilterReportDao $filter_report_dao,
    ) {
        parent::__construct(self::ID);
        $this->setOwner(
            (int) $project->getID(),
            \Tuleap\Dashboard\Project\ProjectDashboardController::LEGACY_DASHBOARD_TYPE
        );
    }

    public function getContent(): string
    {
        Prometheus::instance()->increment(
            'plugin_roadmap_project_widget_display_total',
            'Total number of display of roadmap project widget',
        );

        return $this->renderer->renderToString(
            'widget-roadmap',
            $this->presenter_builder->getPresenter(
                (int) $this->content_id,
                $this->lvl1_iteration_tracker_id,
                $this->lvl2_iteration_tracker_id,
                $this->default_timescale,
                $this->getCurrentUser(),
            )
        );
    }

    public function isAjax(): bool
    {
        return false;
    }

    public function getIcon(): string
    {
        return "fa-stream";
    }

    public function isUnique(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return $this->title ?: dgettext('tuleap-roadmap', 'Roadmap');
    }

    public function getCategory(): string
    {
        return dgettext('tuleap-tracker', 'Trackers');
    }

    /**
     * @param string $widget_id
     */
    public function hasPreferences($widget_id): bool
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        return $this->renderer->renderToString(
            'preferences-form',
            new PreferencePresenter(
                (string) $widget_id,
                $this->getTitle(),
                $this->tracker_ids,
                $this->filter_report_dao->getReportIdToFilterArtifacts($content_id),
                $this->default_timescale,
                $this->lvl1_iteration_tracker_id,
                $this->lvl2_iteration_tracker_id,
                $this->tracker_factory->getTrackersByGroupIdUserCanView(
                    $this->owner_id,
                    $this->getCurrentUser()
                )
            )
        );
    }

    public function getInstallPreferences(): string
    {
        return $this->renderer->renderToString(
            'preferences-form',
            new PreferencePresenter(
                self::ID,
                $this->getTitle(),
                null,
                null,
                self::DEFAULT_TIMESCALE_MONTH,
                null,
                null,
                $this->tracker_factory->getTrackersByGroupIdUserCanView(
                    $this->owner_id,
                    $this->getCurrentUser()
                )
            )
        );
    }

    /**
     * @param int|string $id
     * @param int|string $owner_id
     * @param string     $owner_type
     */
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ): int {
        if (! $mapping_registry->hasCustomMapping(TrackerFactory::TRACKER_MAPPING_KEY)) {
            return $this->dao->cloneContent(
                (int) $id,
                (int) $owner_id,
                $owner_type
            );
        }

        return $this->transaction_executor->execute(
            function () use ($id, $owner_id, $owner_type, $mapping_registry): int {
                $data = $this->dao->searchContent((int) $id, (int) $this->owner_id, (string) $this->owner_type);
                if (! $data) {
                    return $this->dao->cloneContent(
                        (int) $id,
                        (int) $owner_id,
                        $owner_type
                    );
                }

                $tracker_mapping           = $mapping_registry->getCustomMapping(TrackerFactory::TRACKER_MAPPING_KEY);
                $lvl1_iteration_tracker_id = $tracker_mapping[$data['lvl1_iteration_tracker_id']] ?? $data['lvl1_iteration_tracker_id'];
                $lvl2_iteration_tracker_id = $tracker_mapping[$data['lvl2_iteration_tracker_id']] ?? $data['lvl2_iteration_tracker_id'];

                return $this->dao->insertContent(
                    (int) $owner_id,
                    $owner_type,
                    $data['title'],
                    array_map(
                        static fn(int $tracker_id): int => $tracker_mapping[$tracker_id] ?? $tracker_id,
                        $this->dao->searchSelectedTrackers((int) $id) ?? [],
                    ),
                    0,
                    $data['default_timescale'],
                    $lvl1_iteration_tracker_id,
                    $lvl2_iteration_tracker_id,
                );
            }
        );
    }

    /**
     * @param string $id
     */
    public function loadContent($id): void
    {
        $row = $this->dao->searchContent(
            (int) $id,
            (int) $this->owner_id,
            (string) $this->owner_type,
        );

        if ($row) {
            $this->default_timescale         = $this->getValidTimescale($row['default_timescale']);
            $this->lvl1_iteration_tracker_id = $row['lvl1_iteration_tracker_id'];
            $this->lvl2_iteration_tracker_id = $row['lvl2_iteration_tracker_id'];
            $this->title                     = $row['title'];
            $this->tracker_ids               = $this->dao->searchSelectedTrackers((int) $id);
            $this->content_id                = $id;
        }
    }

    /**
     * @return false|int
     */
    public function create(Codendi_Request $request)
    {
        $roadmap_parameters = $request->get('roadmap');
        if (! is_array($roadmap_parameters)) {
            return false;
        }

        if (! isset($roadmap_parameters['title'], $roadmap_parameters['tracker_ids'])) {
            return false;
        }

        $tracker_ids = array_map(
            static fn(string $tracker_id): int => (int) $tracker_id,
            $roadmap_parameters['tracker_ids']
        );
        foreach ($tracker_ids as $tracker_id) {
            if ($tracker_id <= 0) {
                return false;
            }
        }

        $lvl1_iteration_tracker_id = isset($roadmap_parameters['lvl1_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl1_iteration_tracker_id'] : null;
        $lvl2_iteration_tracker_id = isset($roadmap_parameters['lvl2_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl2_iteration_tracker_id'] : null;

        $default_timescale = $this->getValidTimescale($roadmap_parameters['default_timescale'] ?? null);

        $report_id = (int) ($roadmap_parameters['filter_report_id'] ?? "");

        return $this->dao->insertContent(
            (int) $this->owner_id,
            (string) $this->owner_type,
            $roadmap_parameters['title'],
            $tracker_ids,
            $report_id,
            $default_timescale,
            $lvl1_iteration_tracker_id,
            $lvl2_iteration_tracker_id,
        );
    }

    private function getValidTimescale(?string $timescale): string
    {
        if (! in_array($timescale, ['week', 'month', 'quarter'], true)) {
            return self::DEFAULT_TIMESCALE_MONTH;
        }

        return $timescale;
    }

    public function updatePreferences(Codendi_Request $request): bool
    {
        $id = (int) $request->get('content_id');
        if (! $id) {
            return false;
        }

        $roadmap_parameters = $request->get('roadmap');
        if (! is_array($roadmap_parameters)) {
            return false;
        }

        if (! isset($roadmap_parameters['title'], $roadmap_parameters['tracker_ids'])) {
            return false;
        }

        $tracker_ids = array_map(
            static fn(string $tracker_id): int => (int) $tracker_id,
            $roadmap_parameters['tracker_ids']
        );
        foreach ($tracker_ids as $tracker_id) {
            if ($tracker_id <= 0) {
                return false;
            }
        }

        $lvl1_iteration_tracker_id = isset($roadmap_parameters['lvl1_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl1_iteration_tracker_id'] : null;
        $lvl2_iteration_tracker_id = isset($roadmap_parameters['lvl2_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl2_iteration_tracker_id'] : null;

        $default_timescale = $this->getValidTimescale($roadmap_parameters['default_timescale'] ?? null);

        $report_id = (int) ($roadmap_parameters['filter_report_id'] ?? "");

        $this->dao->update(
            $id,
            (int) $this->owner_id,
            (string) $this->owner_type,
            $roadmap_parameters['title'],
            $tracker_ids,
            $report_id,
            $default_timescale,
            $lvl1_iteration_tracker_id,
            $lvl2_iteration_tracker_id,
        );

        return true;
    }

    /**
     * @param string $id
     */
    public function destroy($id): void
    {
        $this->dao->delete((int) $id, (int) $this->owner_id, (string) $this->owner_type);
    }

    public function getJavascriptAssets(): array
    {
        return [
            new JavascriptAsset($this->getWidgetAssets(), 'widget-script.js'),
            new JavascriptViteAsset($this->getConfigureWidgetAssets(), 'src/index.ts'),
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([
            new CssAssetWithoutVariantDeclinaisons($this->getWidgetAssets(), 'widget-style'),
        ]);
    }

    private function getWidgetAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../scripts/roadmap-widget/frontend-assets',
            '/assets/roadmap/roadmap-widget'
        );
    }

    private function getConfigureWidgetAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../../scripts/configure-widget/frontend-assets',
            '/assets/roadmap/configure-widget'
        );
    }

    public function exportAsXML(): ?\SimpleXMLElement
    {
        $widget = new \SimpleXMLElement('<widget />');
        $widget->addAttribute('name', $this->id);

        $preference = $widget->addChild('preference');
        if ($preference === null) {
            return null;
        }
        $preference->addAttribute('name', 'roadmap');

        $cdata_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            (string) $this->title,
            ['name' => 'title']
        );
        $cdata_factory->insertWithAttributes(
            $preference,
            'value',
            $this->default_timescale,
            ['name' => 'default_timescale']
        );

        if ($this->tracker_ids) {
            foreach ($this->tracker_ids as $tracker_id) {
                $this->addPreferenceXmlNodeValue($preference, 'tracker_id', \Tracker::XML_ID_PREFIX . $tracker_id);
            }
        }

        $report_id = $this->filter_report_dao->getReportIdToFilterArtifacts((int) $this->content_id);
        if ($report_id) {
            $this->addPreferenceXmlNodeValue($preference, 'report_id', \Tracker_Report::XML_ID_PREFIX . $report_id);
        }

        if ($this->lvl1_iteration_tracker_id) {
            $this->addPreferenceXmlNodeValue($preference, 'lvl1_iteration_tracker_id', \Tracker::XML_ID_PREFIX . $this->lvl1_iteration_tracker_id);
        }

        if ($this->lvl2_iteration_tracker_id) {
            $this->addPreferenceXmlNodeValue($preference, 'lvl2_iteration_tracker_id', \Tracker::XML_ID_PREFIX . $this->lvl2_iteration_tracker_id);
        }

        return $widget;
    }

    private function addPreferenceXmlNodeValue(\SimpleXMLElement $preference, string $name, string $value): void
    {
        $child = $preference->addChild('value', $value);
        if ($child === null) {
            return;
        }

        $child->addAttribute('name', $name);
    }
}
