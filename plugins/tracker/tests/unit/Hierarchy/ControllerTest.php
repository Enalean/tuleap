<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Hierarchy\HierarchyController;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Hierarchy\TrackerHierarchyUpdateEvent;

final class Tracker_Hierarchy_ControllerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var Codendi_Request|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $request;
    /**
     * @var Tracker_Hierarchy_HierarchicalTracker
     */
    private $hierarchical_tracker;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Hierarchy_HierarchicalTrackerFactory
     */
    private $factory;
    /**
     * @var \Mockery\MockInterface|HierarchyDAO
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface|Tracker_Workflow_Trigger_RulesDao
     */
    private $trigger_rules_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;
    /**
     * @var EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;
    private ProjectHistoryDao&\PHPUnit\Framework\MockObject\MockObject $project_history_dao;

    protected function setUp(): void
    {
        $this->tracker_id = 3;
        $project          = Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(101);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $tracker->shouldReceive('getName')->andReturn('Stories');

        $this->hierarchical_tracker     = new Tracker_Hierarchy_HierarchicalTracker($tracker, []);
        $this->request                  = Mockery::mock(Codendi_Request::class);
        $this->dao                      = \Mockery::spy(HierarchyDAO::class);
        $this->factory                  = \Mockery::spy(\Tracker_Hierarchy_HierarchicalTrackerFactory::class);
        $this->trigger_rules_dao        = Mockery::spy(Tracker_Workflow_Trigger_RulesDao::class);
        $this->artifact_links_usage_dao = Mockery::mock(ArtifactLinksUsageDao::class);
        $this->event_manager            = $this->createMock(EventManager::class);
        $this->project_history_dao      = $this->createMock(ProjectHistoryDao::class);

        $this->request->shouldReceive('getCurrentUser')->andReturn(\Tuleap\Test\Builders\UserTestBuilder::aUser()->build());
        $this->trigger_rules_dao->shouldReceive('searchTriggeringTrackersByTargetTrackerID')->andReturn([]);
    }

    public function testEditListsAllChildren(): void
    {
        $possible_children = [
            '11' => $this->getTrackerWithIdAndName(11, 'Bugs'),
            '22' => $this->getTrackerWithIdAndName(22, 'Tasks'),
        ];

        $this->factory->shouldReceive('getPossibleChildren')->with($this->hierarchical_tracker)
            ->andReturns($possible_children);
        $this->factory->shouldReceive('getHierarchy')->andReturns($this->getHierarchyAsTreeNode([]));

        $presenter = $this->buildPresenter();

        $possible_children = $presenter->getPossibleChildren();

        $this->assertEquals(
            [
                ['id' => 11, 'name' => 'Bugs', 'selected' => false],
                ['id' => 22, 'name' => 'Tasks', 'selected' => false],
            ],
            $possible_children
        );
    }

    private function getHierarchyAsTreeNode($hierarchy): \TreeNode
    {
        $node = new TreeNode();
        if (isset($hierarchy['children'])) {
            $node->setData(['name' => $hierarchy['name'], 'id' => $hierarchy['id'], 'current_class' => '']);
            $node->setId($hierarchy['id']);
            $hierarchy = $hierarchy['children'];
        } else {
            $node->setId('root');
        }
        foreach ($hierarchy as $item) {
            $node->addChild($this->getHierarchyAsTreeNode($item));
        }

        return $node;
    }

    public function testItBuildTheTrackersInTheHierarchy(): void
    {
        $sprints_id = 666;
        $stories_id = 999;
        $hierarchy  = [
            [
                'name' => 'Sprints',
                'id' => $sprints_id,
                'current_class' => '',
                'children' => [
                    ['name' => 'Stories', 'id' => $stories_id, 'current_class' => '', 'children' => []],
                ],
            ],
        ];
        $this->factory->shouldReceive('getPossibleChildren')->andReturns([]);
        $this->factory->shouldReceive('getHierarchy')->once()->andReturns(
            $this->getHierarchyAsTreeNode($hierarchy)
        );

        $presenter = $this->buildPresenter();

        $hierarchy = $presenter->hierarchy->flattenChildren();

        $this->assertCount(2, $hierarchy);

        $sprint_child = $hierarchy[0];
        $this->assertEquals('Sprints', $sprint_child->getData()['name']);
        $sprint_child = $hierarchy[1];
        $this->assertEquals('Stories', $sprint_child->getData()['name']);
    }

    private function buildPresenter(): Tracker_Hierarchy_Presenter
    {
        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );
        return $controller->buildPresenter();
    }

    public function testUpdateHappyPathShouldCallDaoToSaveHierarchy(): void
    {
        $children_ids = ['1', '2'];
        $this->mockRequestChildren($children_ids);
        $this->dao->shouldReceive('updateChildren')->with($this->tracker_id, $children_ids)->once();

        $this->artifact_links_usage_dao->shouldReceive('isProjectUsingArtifactLinkTypes')
            ->once()
            ->andReturnFalse();

        $this->event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $this->hierarchical_tracker->getUnhierarchizedTracker(),
                $children_ids,
            )
        );

        $this->project_history_dao->expects(self::once())->method('addHistory');

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );
        $controller->update();
    }

    public function testItUpdatesInHappyPathShouldCallDaoToChangeTheHierarchyOnly(): void
    {
        $children_ids = ['1', '2'];
        $this->mockRequestChildren($children_ids);
        $this->dao->shouldReceive('changeTrackerHierarchy')->with($this->tracker_id, $children_ids)->once();

        $this->artifact_links_usage_dao->shouldReceive('isProjectUsingArtifactLinkTypes')
            ->once()
            ->andReturnTrue();

        $this->event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $this->hierarchical_tracker->getUnhierarchizedTracker(),
                $children_ids,
            )
        );

        $this->project_history_dao->expects(self::once())->method('addHistory');

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );
        $controller->update();
    }

    public function testWeCanDeleteAllChildrenByNOTProvidingAnArrayOfIds(): void
    {
        $children_ids = [];
        $this->mockRequestChildren($children_ids);
        $this->dao->shouldReceive('updateChildren')->with($this->tracker_id, $children_ids)->once();

        $this->artifact_links_usage_dao->shouldReceive('isProjectUsingArtifactLinkTypes')
            ->once()
            ->andReturnFalse();

        $this->event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $this->hierarchical_tracker->getUnhierarchizedTracker(),
                $children_ids,
            )
        );

        $this->project_history_dao->expects(self::once())->method('addHistory');

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );

        $controller->update();
    }

    public function testUpdateWithNastyRequestShouldThrowErrors(): void
    {
        $children_ids = ['DROP DATABASE http://xkcd.com/327/'];
        $this->request->shouldReceive('get')->with('children')->andReturn($children_ids);
        $this->request->shouldReceive('validArray')->andReturnFalse();
        $this->request->shouldReceive('exist')->andReturnTrue();

        $this->dao->shouldReceive('updateChildren')->never();

        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );
        $controller->update();
    }

    public function testItCreatesHierarchyFromXmlProjectImportProcess(): void
    {
        $mapping    = [111, 222, 333, 444];
        $controller = new HierarchyController(
            $this->request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );
        $this->dao->shouldReceive('updateChildren')->once();

        $controller->updateFromXmlProjectImportProcess($mapping);
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private function getTrackerWithIdAndName(int $id, string $name)
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($id);
        $tracker->shouldReceive('getName')->andReturn($name);

        return $tracker;
    }

    private function mockRequestChildren(array $children_ids): void
    {
        $this->request->shouldReceive('get')->with('children')->andReturn($children_ids);
        $this->request->shouldReceive('validArray')->andReturnTrue();
        $this->request->shouldReceive('exist')->with('children')->andReturnTrue();
    }
}
