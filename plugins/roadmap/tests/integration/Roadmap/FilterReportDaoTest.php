<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class FilterReportDaoTest extends TestIntegrationTestCase
{
    public function testSaveReportId(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (344, 666, null)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (1001, 666, 101)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (1002, 777, null)");

        $dao                 = new FilterReportDao();
        $non_existing_report = 111;
        $dao->saveReportId(123, $non_existing_report);
        self::assertFalse($this->getSavedReportId(123));

        $private_report = 1001;
        $dao->saveReportId(123, $private_report);
        self::assertFalse($this->getSavedReportId(123));

        $public_report_of_tracker_not_selected_by_roadmap = 1002;
        $dao->saveReportId(123, $public_report_of_tracker_not_selected_by_roadmap);
        self::assertFalse($this->getSavedReportId(123));

        $dao->saveReportId(123, 979);
        self::assertEquals(979, $this->getSavedReportId(123));

        $dao->saveReportId(123, 344);
        self::assertEquals(344, $this->getSavedReportId(123));
    }

    private function getSavedReportId(int $widget_id): int|false
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        return $db->cell("SELECT report_id FROM plugin_roadmap_widget_filter WHERE widget_id = ?", $widget_id);
    }

    public function testGetReportIdToFilterArtifactsReturnsNullIfNoReportIsSavedForWidget(): void
    {
        $dao = new FilterReportDao();

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsTheReportIdSavedForWidget(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");

        $dao = new FilterReportDao();
        $dao->saveReportId(123, 979);

        self::assertEquals(979, $dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsNullWhenReportIsNotForSelectedTracker(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 111, null)");

        $dao = new FilterReportDao();
        $dao->saveReportId(123, 979);

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsNullWhenReportIsAPersonalOne(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, 101)");

        $dao = new FilterReportDao();
        $dao->saveReportId(123, 979);

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsNullIfWidgetIsConfiguredForMoreThanOneTracker(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 667)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");

        $dao = new FilterReportDao();
        $dao->saveReportId(123, 979);

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testDeletionByReport(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (124, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");

        $dao = new FilterReportDao();
        $dao->saveReportId(123, 979);
        $dao->saveReportId(124, 979);

        self::assertEquals(979, $dao->getReportIdToFilterArtifacts(123));
        self::assertEquals(979, $dao->getReportIdToFilterArtifacts(124));

        $dao->deleteByReport(979);

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
        self::assertNull($dao->getReportIdToFilterArtifacts(124));
    }

    public function testDeletionByWidget(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");

        $dao = new FilterReportDao();
        $dao->saveReportId(123, 979);
        self::assertEquals(979, $dao->getReportIdToFilterArtifacts(123));

        $dao->deleteByWidget(123);
        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }
}
