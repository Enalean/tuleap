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

final class RoadmapWidgetDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM plugin_roadmap_widget_filter');
        $db->run('DELETE FROM plugin_roadmap_widget_trackers');
    }

    public function testWidgetDeletionDeleteAssociatedData(): void
    {
        $dao = new RoadmapWidgetDao();
        $id  = $dao->insertContent(101, 'g', 'My Roadmap', [666], 'month', null, null);

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("INSERT INTO plugin_roadmap_widget_filter(widget_id, report_id) VALUES (?, 979)", $id);

        self::assertNotEmpty($db->run('SELECT * FROM plugin_roadmap_widget_filter WHERE widget_id = ?', $id));

        $dao->delete($id, 101, 'g');

        self::assertEmpty($db->run('SELECT * FROM plugin_roadmap_widget_filter WHERE widget_id = ?', $id));
    }
}
