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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Hierarchy\HierarchyController;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Hierarchy\TrackerHierarchyUpdateEvent;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Hierarchy_ControllerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;

    private Tracker_Hierarchy_HierarchicalTracker $hierarchical_tracker;
    private int $tracker_id;
    private Tracker_Hierarchy_HierarchicalTrackerFactory&MockObject $factory;
    private HierarchyDAO&MockObject $dao;
    private Tracker_Workflow_Trigger_RulesDao&MockObject $trigger_rules_dao;
    private ArtifactLinksUsageDao&MockObject $artifact_links_usage_dao;
    private EventManager&MockObject $event_manager;
    private ProjectHistoryDao&MockObject $project_history_dao;

    protected function setUp(): void
    {
        $this->tracker_id = 3;
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withId($this->tracker_id)
            ->withName('Stories')
            ->build();

        $this->hierarchical_tracker     = new Tracker_Hierarchy_HierarchicalTracker($tracker, []);
        $this->dao                      = $this->createMock(HierarchyDAO::class);
        $this->factory                  = $this->createMock(Tracker_Hierarchy_HierarchicalTrackerFactory::class);
        $this->trigger_rules_dao        = $this->createMock(Tracker_Workflow_Trigger_RulesDao::class);
        $this->artifact_links_usage_dao = $this->createMock(ArtifactLinksUsageDao::class);
        $this->event_manager            = $this->createMock(EventManager::class);
        $this->project_history_dao      = $this->createMock(ProjectHistoryDao::class);

        $this->trigger_rules_dao->method('searchTriggeringTrackersByTargetTrackerID')->willReturn([]);
    }

    public function testEditListsAllChildren(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $possible_children = [
            '11' => $this->getTrackerWithIdAndName(11, 'Bugs'),
            '22' => $this->getTrackerWithIdAndName(22, 'Tasks'),
        ];

        $this->factory->method('getPossibleChildren')->with($this->hierarchical_tracker)
            ->willReturn($possible_children);
        $this->factory->method('getHierarchy')->willReturn($this->getHierarchyAsTreeNode([]));

        $presenter = $this->buildPresenter($request);

        $possible_children = $presenter->getPossibleChildren();

        $this->assertEquals(
            [
                ['id' => 11, 'name' => 'Bugs', 'selected' => false],
                ['id' => 22, 'name' => 'Tasks', 'selected' => false],
            ],
            $possible_children
        );
    }

    private function getHierarchyAsTreeNode($hierarchy): TreeNode
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
        $request = HTTPRequestBuilder::get()->build();

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
        $this->factory->method('getPossibleChildren')->willReturn([]);
        $this->factory->expects(self::once())->method('getHierarchy')->willReturn(
            $this->getHierarchyAsTreeNode($hierarchy)
        );

        $presenter = $this->buildPresenter($request);

        $hierarchy = $presenter->hierarchy->flattenChildren();

        $this->assertCount(2, $hierarchy);

        $sprint_child = $hierarchy[0];
        $this->assertEquals('Sprints', $sprint_child->getData()['name']);
        $sprint_child = $hierarchy[1];
        $this->assertEquals('Stories', $sprint_child->getData()['name']);
    }

    private function buildController(Codendi_Request $request): HierarchyController
    {
        return new HierarchyController(
            $request,
            $this->hierarchical_tracker,
            $this->factory,
            $this->dao,
            $this->trigger_rules_dao,
            $this->artifact_links_usage_dao,
            $this->event_manager,
            $this->project_history_dao
        );
    }

    private function buildPresenter(Codendi_Request $request): Tracker_Hierarchy_Presenter
    {
        return $this->buildController($request)->buildPresenter();
    }

    public function testUpdateHappyPathShouldCallDaoToSaveHierarchy(): void
    {
        $children_ids = ['1', '2'];
        $request      = HTTPRequestBuilder::get()
            ->withParam('children', $children_ids)
            ->build();

        $this->dao->expects(self::once())->method('updateChildren')->with($this->tracker_id, $children_ids);

        $this->artifact_links_usage_dao
            ->expects(self::once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(false);

        $this->event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $this->hierarchical_tracker->getUnhierarchizedTracker(),
                $children_ids,
            )
        );

        $this->project_history_dao->expects(self::once())->method('addHistory');

        $this->buildController($request)->update();
    }

    public function testItUpdatesInHappyPathShouldCallDaoToChangeTheHierarchyOnly(): void
    {
        $children_ids = ['1', '2'];
        $request      = HTTPRequestBuilder::get()
            ->withParam('children', $children_ids)
            ->build();

        $this->dao->expects(self::once())->method('changeTrackerHierarchy')->with($this->tracker_id, $children_ids);

        $this->artifact_links_usage_dao
            ->expects(self::once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(true);

        $this->event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $this->hierarchical_tracker->getUnhierarchizedTracker(),
                $children_ids,
            )
        );

        $this->project_history_dao->expects(self::once())->method('addHistory');

        $this->buildController($request)->update();
    }

    public function testWeCanDeleteAllChildrenByNOTProvidingAnArrayOfIds(): void
    {
        $children_ids = [];

        // We should have been able to use a builder like the following to setup the request,
        // however there is an issue with request validation when we give an empty array.
        // Since it is out of scope (switch to MockObject) to look at this, we fallback to a mock.
        // $request = HTTPRequestBuilder::get()
        //     ->withParam('children', $children_ids)
        //     ->build();
        $request = $this->createMock(Codendi_Request::class);
        $request->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $request->method('get')->with('children')->willReturn($children_ids);
        $request->method('validArray')->willReturn(true);
        $request->method('exist')->with('children')->willReturn(true);

        $this->dao->expects(self::once())->method('updateChildren')->with($this->tracker_id, $children_ids);

        $this->artifact_links_usage_dao
            ->expects(self::once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(false);

        $this->event_manager->method('dispatch')->willReturn(
            new TrackerHierarchyUpdateEvent(
                $this->hierarchical_tracker->getUnhierarchizedTracker(),
                $children_ids,
            )
        );

        $this->project_history_dao->expects(self::once())->method('addHistory');

        $this->buildController($request)->update();
    }

    public function testUpdateWithNastyRequestShouldThrowErrors(): void
    {
        $children_ids = ['DROP DATABASE http://xkcd.com/327/'];
        $request      = HTTPRequestBuilder::get()
            ->withParam('children', $children_ids)
            ->build();

        $this->dao->expects(self::never())->method('updateChildren');

        $this->buildController($request)->update();
    }

    public function testItCreatesHierarchyFromXmlProjectImportProcess(): void
    {
        $request = HTTPRequestBuilder::get()
            ->build();

        $mapping = [111, 222, 333, 444];
        $this->dao->expects(self::once())->method('updateChildren');

        $this->buildController($request)->updateFromXmlProjectImportProcess($mapping);
    }

    /**
     * @return LegacyMockInterface|MockInterface|Tracker
     */
    private function getTrackerWithIdAndName(int $id, string $name)
    {
        return TrackerTestBuilder::aTracker()->withId($id)->withName($name)->build();
    }
}
