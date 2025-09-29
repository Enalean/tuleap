<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Kanban\XML;

use Exception;
use Project;
use SimpleXMLElement;
use Tracker_Report;
use Tracker_ReportFactory;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\Service\KanbanService;
use Tuleap\Kanban\TrackerReport\TrackerReportBuilder;
use Tuleap\Kanban\TrackerReport\TrackerReportDao;
use XML_ParseException;
use XML_RNGValidator;

final readonly class KanbanXMLExporter
{
    private const string NODE_KANBAN_LIST     = 'kanban_list';
    private const string NODE_KANBAN          = 'kanban';
    private const string NODE_TRACKER_REPORTS = 'tracker-reports';
    private const string NODE_TRACKER_REPORT  = 'tracker-report';

    public const string TRACKER_ID_PREFIX = 'T';
    public const string KANBAN_ID_PREFIX  = 'K';

    public function __construct(
        private KanbanFactory $kanban_factory,
        private Tracker_ReportFactory $tracker_report_factory,
        private TrackerReportDao $tracker_report_dao,
        private XML_RNGValidator $xml_validator,
    ) {
    }

    /**
     * @throws XML_ParseException
     * @throws Exception
     */
    public function export(SimpleXMLElement $xml_element, Project $project): void
    {
        if (! $project->usesService(KanbanService::SERVICE_SHORTNAME)) {
            return;
        }

        $agiledashboard_node = $this->getAgiledashboardNode($xml_element);

        $kanban_list_node = $agiledashboard_node->addChild(self::NODE_KANBAN_LIST);
        if ($kanban_list_node === null) {
            throw new Exception('Unable to create kanban_list node');
        }

        $kanban_tracker_ids = $this->kanban_factory->getKanbanTrackerIds((int) $project->getID());
        foreach ($kanban_tracker_ids as $tracker_id) {
            $kanban = $this->kanban_factory->getKanbanByTrackerId($tracker_id);

            if ($kanban === null) {
                continue;
            }

            $kanban_node = $kanban_list_node->addChild(self::NODE_KANBAN);
            if ($kanban_node === null) {
                throw new Exception('Unable to create kanban node');
            }
            $kanban_node->addAttribute('tracker_id', $this->getFormattedTrackerId($tracker_id));
            $kanban_node->addAttribute('name', $kanban->getName());
            $kanban_node->addAttribute('is_promoted', $kanban->is_promoted ? '1' : '0');
            $kanban_node->addAttribute('ID', $this->getFormattedKanbanId($kanban->getId()));

            $report_builder = new TrackerReportBuilder($this->tracker_report_factory, $kanban, $this->tracker_report_dao);
            $reports        = $report_builder->build(0);
            if ($reports !== []) {
                $reports_node = $kanban_node->addChild(self::NODE_TRACKER_REPORTS);
                if ($reports_node === null) {
                    throw new Exception('Unable to create tracker-reports node');
                }
                foreach ($reports as $report) {
                    $this->addTrackerReportNode($reports_node, $report);
                }
            }
        }

        $rng_path = realpath(__DIR__ . '/../../../resources/kanban.rng');
        $this->xml_validator->validate($kanban_list_node, $rng_path);
    }

    /**
     * @throws Exception
     */
    private function addTrackerReportNode(SimpleXMLElement $reports_node, array $report): void
    {
        if (! isset($report['selectable']) || ! $report['selectable']) {
            return;
        }

        $report_node = $reports_node->addChild(self::NODE_TRACKER_REPORT);
        if ($report_node === null) {
            throw new Exception('Unable to create tracker-report node');
        }

        $report_node->addAttribute('id', Tracker_Report::XML_ID_PREFIX . $report['id']);
    }

    /**
     * @throws Exception
     */
    private function getAgiledashboardNode(SimpleXMLElement $xml_element): SimpleXMLElement
    {
        $existing_agiledashboard_node = $xml_element->agiledashboard;
        if ($existing_agiledashboard_node) {
            return $existing_agiledashboard_node;
        }

        $agiledashboard_node = $xml_element->addChild('agiledashboard');
        if ($agiledashboard_node === null) {
            throw new Exception('Unable to create agiledashboard node');
        }

        return $agiledashboard_node;
    }

    private function getFormattedTrackerId(int $tracker_id): string
    {
        return self::TRACKER_ID_PREFIX . $tracker_id;
    }

    private function getFormattedKanbanId(int $kanban_id): string
    {
        return self::KANBAN_ID_PREFIX . $kanban_id;
    }
}
