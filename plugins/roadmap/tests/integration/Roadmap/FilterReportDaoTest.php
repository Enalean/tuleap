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

final class FilterReportDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM plugin_roadmap_widget_filter');
        $db->run('DELETE FROM plugin_roadmap_widget_trackers');
        $db->run('DELETE FROM tracker_report');
    }

    public function testGetReportIdToFilterArtifactsReturnsNullIfNoReportIsSavedForWidget(): void
    {
        $dao = new FilterReportDao();

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsTheReportIdSavedForWidget(): void
    {
        $dao = new FilterReportDao();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_filter(widget_id, report_id) VALUES (123, 979)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");

        self::assertEquals(979, $dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsNullWhenReportIsNotForSelectedTracker(): void
    {
        $dao = new FilterReportDao();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_filter(widget_id, report_id) VALUES (123, 979)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 111, null)");

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsNullWhenReportIsAPersonalOne(): void
    {
        $dao = new FilterReportDao();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_filter(widget_id, report_id) VALUES (123, 979)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, 101)");

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }

    public function testGetReportIdToFilterArtifactsReturnsNullIfWidgetIsConfiguredForMoreThanOneTracker(): void
    {
        $dao = new FilterReportDao();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_filter(widget_id, report_id) VALUES (123, 979)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 666)");
        $db->run("INSERT INTO plugin_roadmap_widget_trackers(plugin_roadmap_widget_id, tracker_id) VALUES (123, 667)");
        $db->run("INSERT INTO tracker_report(id, tracker_id, user_id) VALUES (979, 666, null)");

        self::assertNull($dao->getReportIdToFilterArtifacts(123));
    }
}
