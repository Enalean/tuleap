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

use Tuleap\Dashboard\Project\ProjectDashboardIsDisplayed;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Roadmap\REST\ResourcesInjector;
use Tuleap\Roadmap\RoadmapProjectWidget;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Roadmap\Widget\RoadmapWidgetPresenterBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Report\Event\TrackerReportDeleted;
use Tuleap\Tracker\Semantic\Progress\Events\GetSemanticProgressUsageEvent;
use Tuleap\Tracker\Semantic\Timeframe\Events\GetSemanticTimeframeUsageEvent;
use Tuleap\Widget\Event\ConfigureAtXMLImport;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class RoadmapPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-roadmap', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-roadmap', 'Roadmap'),
                    dgettext('tuleap-roadmap', 'Displays project roadmap as a widget')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker'];
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event): void
    {
        if ($get_widget_event->getName() === RoadmapProjectWidget::ID) {
            $get_widget_event->setWidget(new RoadmapProjectWidget(
                HTTPRequest::instance()->getProject(),
                new RoadmapWidgetDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates'),
                new RoadmapWidgetPresenterBuilder(
                    new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao()),
                    TrackerFactory::instance(),
                ),
                TrackerFactory::instance()
            ));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerReportDeleted(TrackerReportDeleted $event): void
    {
        (new \Tuleap\Roadmap\FilterReportDao())->deleteByReport((int) $event->getReport()->getId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event): void
    {
        $event->addWidget(RoadmapProjectWidget::ID);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function configureAtXMLImport(ConfigureAtXMLImport $event): void
    {
        (new Tuleap\Roadmap\Widget\RoadmapConfigureAtXMLImport())->configure($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getSemanticProgressUsageEvent(GetSemanticProgressUsageEvent $event): void
    {
        $event->addUsageLocation(
            dgettext('tuleap-roadmap', 'the Roadmap widget')
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getSemanticTimeframeUsageEvent(GetSemanticTimeframeUsageEvent $event): void
    {
        $event->addUsageLocation(
            dgettext('tuleap-roadmap', 'Roadmap widget')
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectDashboardIsDisplayed(ProjectDashboardIsDisplayed $event): void
    {
        $layout = $event->getLayout();
        $assets = $this->getConfigureWidgetAssets();
        $layout->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset($assets, 'src/index.ts')
        );
    }

    private function getConfigureWidgetAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../scripts/configure-widget/frontend-assets',
            '/assets/roadmap/configure-widget'
        );
    }
}
