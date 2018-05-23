<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\DB\DBFactory;
use Tuleap\TextualReport\ArtifactsPresentersBuilder;
use Tuleap\TextualReport\ExportOptionsMenuItemsAppender;
use Tuleap\TextualReport\SinglePageExporter;
use Tuleap\TextualReport\SinglePagePresenterBuilder;
use Tuleap\Tracker\Report\Renderer\Table\GetExportOptionsMenuItemsEvent;
use Tuleap\Tracker\Report\Renderer\Table\ProcessExportEvent;

require_once __DIR__ . '/../vendor/autoload.php';

class textualreportPlugin extends Plugin // @codingStandardsIgnoreLine
{
    const NAME = 'textualreport';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);

        bindtextdomain('tuleap-textualreport', __DIR__ . '/../site-content');
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\TextualReport\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(GetExportOptionsMenuItemsEvent::NAME);
        $this->addHook(ProcessExportEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getExportOptionsMenuItems(GetExportOptionsMenuItemsEvent $event)
    {
        $appender = new ExportOptionsMenuItemsAppender($this->getRenderer());
        $appender->appendTextualReportDownloadLink($event);
    }

    public function processExport(ProcessExportEvent $event)
    {
        if (! $event->hasKeyInParameters(ExportOptionsMenuItemsAppender::EXPORT_SINGLE_PAGE)) {
            return;
        }

        $matching_ids = $event->getReport()->getMatchingIds();
        $columns      = $event->getRendererTable()->getColumns();

        // We only need sorted artifacts, no need to execute all queries
        $queries = $event->getRendererTable()->buildOrderedQuery($matching_ids, $columns);
        if (empty($queries)) {
            $results = [];
        } else {
            $db      = DBFactory::getMainTuleapDB();
            $results = $db->run($queries[0]);
        }

        $single_page_exporter = new SinglePageExporter(
            new SinglePagePresenterBuilder(
                new ArtifactsPresentersBuilder(Tracker_ArtifactFactory::instance())
            ),
            $this->getRenderer()
        );
        $single_page_exporter->exportAsSinglePage(
            $event->getReport()->getTracker(),
            $results,
            $event->getCurrentUser(),
            $event->getServerUrl()
        );
    }

    /**
     * @return TemplateRenderer
     */
    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }
}
