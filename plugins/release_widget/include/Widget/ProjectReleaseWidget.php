<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\ReleaseWidget\Widget;

use HTTPRequest;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use TemplateRenderer;
use TemplateRendererFactory;
use TrackerFactory;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;
use Widget;

class ProjectReleaseWidget extends Widget
{
    public const NAME = 'release';
    /**
     * @var null|string
     */
    private $label_tracker_backlog;
    /**
     * @var false|\Planning
     */
    private $root_planning;

    /**
     * @var bool
     */
    private $is_ie_11;

    public function __construct()
    {
        $planning_factory = new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );

        $http           = HTTPRequest::instance();
        $this->is_ie_11 = $http->getBrowser()->isIE11();
        $project_id     = $http->getProject()->getID();

        $this->root_planning = $planning_factory->getRootPlanning($http->getCurrentUser(), $project_id);

        if (! $this->root_planning) {
            return;
        }

        $this->label_tracker_backlog = $this->root_planning->getPlanningTracker()->getName();

        parent::__construct(self::NAME);
    }

    public function getTitle() : string
    {
        if ($this->label_tracker_backlog) {
            return sprintf(dgettext('tuleap-release_widget', '%s Widget'), $this->label_tracker_backlog);
        }

        return dgettext('tuleap-release_widget', 'Milestone Widget');
    }

    public function getDescription() : string
    {
        if ($this->label_tracker_backlog) {
            return sprintf(dgettext('tuleap-release_widget', 'A widget for %s monitoring.'), $this->label_tracker_backlog);
        }

        return dgettext('tuleap-release_widget', 'A widget for milestone monitoring.');
    }

    public function getIcon()
    {
        return "fa-map-signs";
    }

    public function getContent() : string
    {
        if ($this->is_ie_11) {
            $message_error = '<p class="tlp-alert-danger">';
            $message_error .= dgettext('tuleap-release_widget', 'The plugin is not supported under IE11. Please use a more recent browser.');
            $message_error .= '</p>';

            return $message_error;
        }

        if (! $this->root_planning) {
            $message_error = '<p class="tlp-alert-danger">';
            $message_error .= dgettext('tuleap-release_widget', 'No root planning is defined.');
            $message_error .= '</p>';

            return $message_error;
        }

        $builder = ProjectReleasePresenterBuilder::build($this->root_planning);

        $renderer = $this->getRenderer(__DIR__ . '/../../templates');

        try {
            return $renderer->renderToString(
                'releasewidget',
                $builder->getProjectReleasePresenter()
            );
        } catch (TimeframeBrokenConfigurationException $e) {
            $message_error = '<p class="tlp-alert-danger">';
            $message_error .= dgettext('tuleap-release_widget', 'Invalid Timeframe Semantic configuration.');
            $message_error .= '</p>';

            return $message_error;
        }
    }

    private function getRenderer(string $template_path) : TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer($template_path);
    }

    public function getJavascriptDependencies()
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/releasewidget/scripts',
            '/assets/releasewidget/scripts'
        );
        return [
            ['file' => $include_assets->getFileURL('releasewidget.js')]
        ];
    }

    public function getStylesheetDependencies()
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/releasewidget/themes/BurningParrot',
            '/assets/releasewidget/themes/BurningParrot'
        );

        return new CssAssetCollection([new CssAsset($include_assets, 'style')]);
    }

    public function getCategory()
    {
        return dgettext('tuleap-release_widget', 'Agile dashboard');
    }
}
