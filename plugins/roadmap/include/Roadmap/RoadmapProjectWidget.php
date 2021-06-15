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
use Tuleap\Project\MappingRegistry;
use Tuleap\Roadmap\Widget\PreferencePresenter;
use Tuleap\Roadmap\Widget\RoadmapWidgetPresenterBuilder;

final class RoadmapProjectWidget extends \Widget
{
    public const ID = 'plugin_roadmap_project_widget';

    private ?int $lvl1_iteration_tracker_id = null;
    private ?int $lvl2_iteration_tracker_id = null;

    /**
     * @var ?int
     */
    private $tracker_id;
    /**
     * @var ?string
     */
    private $title;
    /**
     * @var \MustacheRenderer|\TemplateRenderer
     */
    private $renderer;
    /**
     * @var RoadmapWidgetDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var RoadmapWidgetPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        Project $project,
        RoadmapWidgetDao $dao,
        DBTransactionExecutor $transaction_executor,
        TemplateRenderer $renderer,
        RoadmapWidgetPresenterBuilder $presenter_builder,
        TrackerFactory $tracker_factory
    ) {
        parent::__construct(self::ID);
        $this->setOwner(
            (int) $project->getID(),
            \Tuleap\Dashboard\Project\ProjectDashboardController::LEGACY_DASHBOARD_TYPE
        );

        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->renderer             = $renderer;
        $this->presenter_builder    = $presenter_builder;
        $this->tracker_factory      = $tracker_factory;
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

    /**
     * @param string $widget_id
     */
    public function getPreferences($widget_id): string
    {
        return $this->renderer->renderToString(
            'preferences-form',
            new PreferencePresenter(
                $widget_id,
                $this->getTitle(),
                $this->tracker_id,
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
        MappingRegistry $mapping_registry
    ): int {
        if (! $mapping_registry->hasCustomMapping(TrackerFactory::TRACKER_MAPPING_KEY)) {
            return $this->dao->cloneContent(
                (int) $this->owner_id,
                (string) $this->owner_type,
                (int) $owner_id,
                $owner_type
            );
        }

        return $this->transaction_executor->execute(
            function () use ($id, $owner_id, $owner_type, $mapping_registry): int {
                $data = $this->dao->searchContent((int) $id, (int) $this->owner_id, (string) $this->owner_type);
                if (! $data) {
                    return $this->dao->cloneContent(
                        (int) $this->owner_id,
                        (string) $this->owner_type,
                        (int) $owner_id,
                        $owner_type
                    );
                }

                $tracker_mapping           = $mapping_registry->getCustomMapping(TrackerFactory::TRACKER_MAPPING_KEY);
                $tracker_id                = $tracker_mapping[$data['tracker_id']] ?? $data['tracker_id'];
                $lvl1_iteration_tracker_id = $tracker_mapping[$data['lvl1_iteration_tracker_id']] ?? $data['lvl1_iteration_tracker_id'];
                $lvl2_iteration_tracker_id = $tracker_mapping[$data['lvl2_iteration_tracker_id']] ?? $data['lvl2_iteration_tracker_id'];

                return $this->dao->insertContent(
                    (int) $owner_id,
                    $owner_type,
                    $data['title'],
                    $tracker_id,
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
            $this->lvl1_iteration_tracker_id = $row['lvl1_iteration_tracker_id'];
            $this->lvl2_iteration_tracker_id = $row['lvl2_iteration_tracker_id'];
            $this->title                     = $row['title'];
            $this->tracker_id                = $row['tracker_id'];
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

        if (! isset($roadmap_parameters['title'], $roadmap_parameters['tracker_id'])) {
            return false;
        }

        $tracker_id = (int) $roadmap_parameters['tracker_id'];
        if ($tracker_id <= 0) {
            return false;
        }

        $lvl1_iteration_tracker_id = isset($roadmap_parameters['lvl1_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl1_iteration_tracker_id'] : null;
        $lvl2_iteration_tracker_id = isset($roadmap_parameters['lvl2_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl2_iteration_tracker_id'] : null;

        return $this->dao->insertContent(
            (int) $this->owner_id,
            (string) $this->owner_type,
            $roadmap_parameters['title'],
            $tracker_id,
            $lvl1_iteration_tracker_id,
            $lvl2_iteration_tracker_id,
        );
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

        if (! isset($roadmap_parameters['title'], $roadmap_parameters['tracker_id'])) {
            return false;
        }

        $tracker_id = (int) $roadmap_parameters['tracker_id'];
        if ($tracker_id <= 0) {
            return false;
        }

        $lvl1_iteration_tracker_id = isset($roadmap_parameters['lvl1_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl1_iteration_tracker_id'] : null;
        $lvl2_iteration_tracker_id = isset($roadmap_parameters['lvl2_iteration_tracker_id']) ? (int) $roadmap_parameters['lvl2_iteration_tracker_id'] : null;

        $this->dao->update(
            $id,
            (int) $this->owner_id,
            (string) $this->owner_type,
            $roadmap_parameters['title'],
            $tracker_id,
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

    public function getJavascriptDependencies(): array
    {
        return [
            [
                'file' => $this->getAssets()->getFileURL('widget-script.js'),
            ],
            [
                'file' => $this->getAssets()->getFileURL('configure-roadmap-widget-script.js'),
            ]
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([
            new CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'widget-style'),
            new CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'configure-roadmap-widget-style'),
        ]);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/roadmap',
            '/assets/roadmap'
        );
    }
}
