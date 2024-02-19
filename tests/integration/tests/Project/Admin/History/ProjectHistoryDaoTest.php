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

namespace Tuleap\Project\Admin\History;

use ProjectHistoryDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ProjectHistoryDaoTest extends TestIntegrationTestCase
{
    private \Project $project;
    private \PFUser $project_admin;
    private \PFUser $another_admin;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $project_admin_id = $this->createUser('project_admin');
        $another_admin_id = $this->createUser('another_admin');

        $this->project       = ProjectTestBuilder::aProject()->build();
        $this->project_admin = UserTestBuilder::aUser()->withId($project_admin_id)->build();
        $this->another_admin = UserTestBuilder::aUser()->withId($another_admin_id)->build();
        $this->now           = new \DateTimeImmutable('now');
    }

    public function testAddHistory(): void
    {
        $dao = new ProjectHistoryDao();
        $dao->addHistory(
            $this->project,
            $this->project_admin,
            $this->now,
            'add',
            'John Doe',
            [201, 202],
        );
        $dao->addHistory(
            $this->project,
            $this->project_admin,
            $this->now->add(new \DateInterval('PT1S')),
            'add',
            'Jane Doe',
        );
        $dao->addHistory(
            $this->project,
            UserTestBuilder::anAnonymousUser()->build(),
            $this->now->add(new \DateInterval('PT2S')),
            'add',
            'By anonymous'
        );

        $result = $dao->getHistory($this->project, 0, 0, null, null, [], null, null, null, null);
        self::assertEquals(3, $result['numrows']);
        self::assertCount(3, $result['history']);

        self::assertEquals('add', $result['history'][0]['field_name']);
        self::assertEquals('By anonymous', $result['history'][0]['old_value']);
        self::assertEquals('None', $result['history'][0]['user_name']);

        self::assertEquals('add', $result['history'][1]['field_name']);
        self::assertEquals('Jane Doe', $result['history'][1]['old_value']);
        self::assertEquals('project_admin', $result['history'][1]['user_name']);

        self::assertEquals('add %% 201||202', $result['history'][2]['field_name']);
        self::assertEquals('John Doe', $result['history'][2]['old_value']);
        self::assertEquals('project_admin', $result['history'][2]['user_name']);
    }

    public function testGetHistoryReturnsPaginatedAndFilteredResultts(): void
    {
        $dao = new ProjectHistoryDao();
        $dao->addHistory(
            $this->project,
            $this->project_admin,
            $this->now,
            'delete',
            'John Doe',
            [201],
        );
        $dao->addHistory(
            $this->project,
            $this->project_admin,
            $this->now->add(new \DateInterval('P5D')),
            'subscribe',
            'John Doe',
        );
        $dao->addHistory(
            $this->project,
            $this->another_admin,
            $this->now->add(new \DateInterval('P10D')),
            'add',
            'Jane Doe',
        );

        // limit
        $all_history_events = [
            'event_user' => [
                'add',
                'delete',
                'move',
            ],
            'event_notifs' => [
                'subscribe',
            ],
        ];

        $result = $dao->getHistory($this->project, 0, 1, null, null, $all_history_events, null, null, null, null);
        self::assertEquals(3, $result['numrows']);
        self::assertCount(1, $result['history']);
        self::assertEquals('add', $result['history'][0]['field_name']);

        // limit + offset
        $result = $dao->getHistory($this->project, 1, 1, null, null, $all_history_events, null, null, null, null);
        self::assertEquals(3, $result['numrows']);
        self::assertCount(1, $result['history']);
        self::assertEquals('subscribe', $result['history'][0]['field_name']);

        // filter event (any)
        $result = $dao->getHistory($this->project, 0, 0, 'any', null, $all_history_events, null, null, null, null);
        self::assertEquals(3, $result['numrows']);
        self::assertCount(3, $result['history']);

        // filter event (event_user)
        $result = $dao->getHistory($this->project, 0, 0, 'event_user', null, $all_history_events, null, null, null, null);
        self::assertEquals(2, $result['numrows']);
        self::assertCount(2, $result['history']);

        // filter sub events
        $result = $dao->getHistory($this->project, 0, 0, 'event_user', ['add' => 1, 'move' => 1], $all_history_events, null, null, null, null);
        self::assertEquals(1, $result['numrows']);
        self::assertCount(1, $result['history']);

        // filter old_value
        $result = $dao->getHistory($this->project, 0, 0, null, null, $all_history_events, 'Jane', null, null, null);
        self::assertEquals(1, $result['numrows']);
        self::assertCount(1, $result['history']);

        // filter start date
        $result = $dao->getHistory($this->project, 0, 0, null, null, $all_history_events, null, $this->now->add(new \DateInterval('P1D')), null, null);
        self::assertEquals(2, $result['numrows']);
        self::assertCount(2, $result['history']);

        // filter end date
        $result = $dao->getHistory($this->project, 0, 0, null, null, $all_history_events, null, null, $this->now->add(new \DateInterval('P7D')), null);
        self::assertEquals(2, $result['numrows']);
        self::assertCount(2, $result['history']);

        // filter start + end date
        $result = $dao->getHistory($this->project, 0, 0, null, null, $all_history_events, null, $this->now->add(new \DateInterval('P1D')), $this->now->add(new \DateInterval('P7D')), null);
        self::assertEquals(1, $result['numrows']);
        self::assertCount(1, $result['history']);

        // filter by
        $result = $dao->getHistory($this->project, 0, 0, null, null, $all_history_events, null, null, null, $this->project_admin);
        self::assertEquals(2, $result['numrows']);
        self::assertCount(2, $result['history']);
    }

    private function createUser(string $user_name): int
    {
        return (new \UserDao())->create(
            $user_name,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );
    }
}
