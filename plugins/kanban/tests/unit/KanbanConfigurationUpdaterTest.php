<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\AgileDashboard\ServiceAdministration\ConfigurationResponse;
use Tuleap\Kanban\Stubs\Legacy\LegacyKanbanActivatorStub;
use Tuleap\Kanban\Stubs\Legacy\LegacyKanbanDeactivatorStub;
use Tuleap\Kanban\Stubs\Legacy\LegacyKanbanRetrieverStub;
use Tuleap\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class KanbanConfigurationUpdaterTest extends TestCase
{
    private const PROJECT_ID = 150;
    private \Codendi_Request & Stub $request;
    private LegacyKanbanRetrieverStub $kanban_retriever;
    private LegacyKanbanActivatorStub $kanban_activator;
    private LegacyKanbanDeactivatorStub $kanban_deactivator;
    private ConfigurationResponse & MockObject $response;

    protected function setUp(): void
    {
        $this->request            = $this->createStub(\Codendi_Request::class);
        $this->kanban_retriever   = LegacyKanbanRetrieverStub::withoutActivatedKanban();
        $this->kanban_activator   = new LegacyKanbanActivatorStub();
        $this->kanban_deactivator = new LegacyKanbanDeactivatorStub();
        $this->response           = $this->createMock(ConfigurationResponse::class);

        $user = UserTestBuilder::buildWithDefaults();
        $this->request->method('getCurrentUser')->willReturn($user);
    }

    private function update(): void
    {
        $kanban_manager = $this->createStub(KanbanManager::class);
        $kanban_manager->method('getTrackersUsedAsKanban')->willReturn([34]);
        $xml_import = $this->createStub(\TrackerXmlImport::class);
        $xml_import->method('getTrackerItemNameFromXMLFile')->willReturn('activity');

        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $updater = new KanbanConfigurationUpdater(
            $this->request,
            $this->kanban_retriever,
            $this->kanban_activator,
            $this->kanban_deactivator,
            $this->response,
            new FirstKanbanCreator(
                $project,
                $kanban_manager,
                $this->createStub(\TrackerFactory::class),
                $xml_import,
                $this->createStub(KanbanFactory::class),
                $this->createStub(TrackerReportUpdater::class),
                $this->createStub(\Tracker_ReportFactory::class)
            )
        );
        $updater->updateConfiguration();
    }

    public function testItActivatesAndCreatesFirstKanban(): void
    {
        $this->request->method('get')->willReturnMap([['group_id', self::PROJECT_ID], ['activate-kanban', '1']]);

        $this->response->expects(self::once())->method('kanbanActivated');

        $this->update();

        self::assertSame(self::PROJECT_ID, $this->kanban_activator->getActivatedProjectId());
    }

    public function testItDoesNothingWhenToldToActivateKanbanAndItIsAlreadyActive(): void
    {
        $this->request->method('get')->willReturnMap([['group_id', self::PROJECT_ID], ['activate-kanban', '1']]);
        $this->kanban_retriever = LegacyKanbanRetrieverStub::withActivatedKanban();

        $this->response->expects(self::never())->method('kanbanActivated');

        $this->update();

        self::assertNull($this->kanban_activator->getActivatedProjectId());
    }

    public function testItDeactivatesKanban(): void
    {
        $this->request->method('get')->willReturnMap([['group_id', self::PROJECT_ID]]);
        $this->kanban_retriever = LegacyKanbanRetrieverStub::withActivatedKanban();

        $this->update();

        self::assertSame(self::PROJECT_ID, $this->kanban_deactivator->getDeactivatedProjectId());
    }

    public function testItDoesNothingWhenToldToDeactivateKanbanAndItIsAlreadyInactive(): void
    {
        $this->request->method('get')->willReturnMap([['group_id', self::PROJECT_ID]]);

        $this->update();

        self::assertNull($this->kanban_deactivator->getDeactivatedProjectId());
    }
}
