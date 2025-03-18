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

namespace Tuleap\Tracker\Hierarchy;

use Codendi_Request;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use Tracker;
use Tracker_Hierarchy_HierarchicalTracker;
use Tracker_Hierarchy_HierarchicalTrackerFactory;
use Tracker_Workflow_Trigger_RulesDao;
use TreeNode;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HierarchyControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 3;
    private Tracker_Hierarchy_HierarchicalTracker $hierarchical_tracker;
    private Tracker_Hierarchy_HierarchicalTrackerFactory&MockObject $factory;
    private HierarchyDAO&MockObject $dao;
    private Tracker_Workflow_Trigger_RulesDao&MockObject $trigger_rules_dao;
    private ArtifactLinksUsageDao&MockObject $artifact_links_usage_dao;
    private EventDispatcherStub $event_manager;
    private ProjectHistoryDao&MockObject $project_history_dao;
    private Tracker $unhierarchized_tracker;
    private LayoutInspector $layout_inspector;

    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->unhierarchized_tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withId(self::TRACKER_ID)
            ->withName('Stories')
            ->build();

        $this->hierarchical_tracker     = new Tracker_Hierarchy_HierarchicalTracker($this->unhierarchized_tracker, []);
        $this->dao                      = $this->createMock(HierarchyDAO::class);
        $this->factory                  = $this->createMock(Tracker_Hierarchy_HierarchicalTrackerFactory::class);
        $this->trigger_rules_dao        = $this->createMock(Tracker_Workflow_Trigger_RulesDao::class);
        $this->artifact_links_usage_dao = $this->createMock(ArtifactLinksUsageDao::class);
        $this->event_manager            = EventDispatcherStub::withIdentityCallback();
        $this->project_history_dao      = $this->createMock(ProjectHistoryDao::class);
        $this->layout_inspector         = new LayoutInspector();
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
            $this->project_history_dao,
            LayoutBuilder::buildWithInspector($this->layout_inspector),
            CSRFSynchronizerTokenStub::buildSelf()
        );
    }

    private function buildPresenter(Codendi_Request $request): HierarchyPresenter
    {
        $this->trigger_rules_dao->method('searchTriggeringTrackersByTargetTrackerID')->willReturn([]);
        return $this->buildController($request)->buildPresenter();
    }

    public function testEditListsAllChildren(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $possible_children = [
            '11' => TrackerTestBuilder::aTracker()->withId(11)->withName('Bugs')->build(),
            '22' => TrackerTestBuilder::aTracker()->withId(22)->withName('Tasks')->build(),
        ];

        $this->factory->method('getPossibleChildren')->with($this->hierarchical_tracker)
            ->willReturn($possible_children);
        $this->factory->method('getHierarchy')->willReturn($this->getHierarchyAsTreeNode([]));

        $presenter = $this->buildPresenter($request);

        $this->assertEquals(
            [
                ['id' => 11, 'name' => 'Bugs', 'selected' => false],
                ['id' => 22, 'name' => 'Tasks', 'selected' => false],
            ],
            $presenter->getPossibleChildren()
        );
    }

    private function getHierarchyAsTreeNode(array $hierarchy): TreeNode
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
                'name'          => 'Sprints',
                'id'            => $sprints_id,
                'current_class' => '',
                'children'      => [
                    ['name' => 'Stories', 'id' => $stories_id, 'current_class' => '', 'children' => []],
                ],
            ],
        ];
        $this->factory->method('getPossibleChildren')->willReturn([]);
        $this->factory->expects($this->once())->method('getHierarchy')->willReturn(
            $this->getHierarchyAsTreeNode($hierarchy)
        );

        $presenter = $this->buildPresenter($request);

        $hierarchy = $presenter->hierarchy->flattenChildren();

        $this->assertCount(2, $hierarchy);

        $sprint_child = $hierarchy[0];
        $this->assertSame('Sprints', $sprint_child->getData()['name']);
        $sprint_child = $hierarchy[1];
        $this->assertSame('Stories', $sprint_child->getData()['name']);
    }

    private function updateWithNoTrigger(Codendi_Request $request): void
    {
        $this->trigger_rules_dao->method('searchTriggeringTrackersByTargetTrackerID')->willReturn([]);
        $this->buildController($request)->update();
    }

    public function testUpdateHappyPathShouldCallDaoToSaveHierarchy(): void
    {
        $children_ids = ['1', '2'];
        $request      = HTTPRequestBuilder::get()
            ->withParam('children', $children_ids)
            ->build();

        $this->artifact_links_usage_dao
            ->expects($this->once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(false);

        $this->dao->expects($this->once())->method('updateChildren')->with(self::TRACKER_ID, $children_ids);
        $this->project_history_dao->expects($this->once())->method('addHistory');

        try {
            $this->updateWithNoTrigger($request);
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection $e) {
            self::assertSame(
                '/plugins/tracker/?tracker=' . self::TRACKER_ID . '&func=' . HierarchyController::HIERARCHY_VIEW,
                $e->redirect_url
            );
        }
    }

    public function testItUpdatesInHappyPathShouldCallDaoToChangeTheHierarchyOnly(): void
    {
        $children_ids = ['1', '2'];
        $request      = HTTPRequestBuilder::get()
            ->withParam('children', $children_ids)
            ->build();

        $this->artifact_links_usage_dao
            ->expects($this->once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(true);

        $this->dao->expects($this->once())->method('changeTrackerHierarchy')->with(self::TRACKER_ID, $children_ids);
        $this->project_history_dao->expects($this->once())->method('addHistory');

        try {
            $this->updateWithNoTrigger($request);
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection $e) {
            self::assertSame(
                '/plugins/tracker/?tracker=' . self::TRACKER_ID . '&func=' . HierarchyController::HIERARCHY_VIEW,
                $e->redirect_url
            );
        }
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

        $this->artifact_links_usage_dao
            ->expects($this->once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(false);

        $this->dao->expects($this->once())->method('updateChildren')->with(self::TRACKER_ID, $children_ids);
        $this->project_history_dao->expects($this->once())->method('addHistory');

        try {
            $this->updateWithNoTrigger($request);
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection) {
        }
    }

    public function testUpdateWithNastyRequestShouldThrowErrors(): void
    {
        $children_ids = ['DROP DATABASE http://xkcd.com/327/'];
        $request      = HTTPRequestBuilder::get()
            ->withParam('children', $children_ids)
            ->build();

        $this->dao->expects($this->never())->method('updateChildren');

        try {
            $this->updateWithNoTrigger($request);
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection) {
        }
        self::assertNotEmpty($this->layout_inspector->getFeedback());
    }

    public function testTrackersImplicatedInTriggersRulesCannotBeRemovedFromTheChildren(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('children', ['147'])
            ->build();

        $this->trigger_rules_dao->method('searchTriggeringTrackersByTargetTrackerID')
            ->willReturn([['tracker_id' => 258]]);
        $child_tracker              = TrackerTestBuilder::aTracker()->withId(258)->build();
        $this->hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker(
            $this->unhierarchized_tracker,
            [$child_tracker]
        );

        $this->artifact_links_usage_dao
            ->expects($this->once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(false);

        $this->project_history_dao->expects($this->once())->method('addHistory');

        $this->dao->expects($this->never())->method('changeTrackerHierarchy');
        $this->dao->method('updateChildren')->willReturnCallback(
            static fn(int $parent_id, array $child_ids) => match (true) {
                $parent_id === self::TRACKER_ID && count($child_ids) === 2 => true,
            }
        );

        try {
            $this->buildController($request)->update();
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection) {
        }
    }

    public function testItDoesRemoveExistingIsChildLinksIfProjectUsesArtifactLinksType(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('children', ['147'])
            ->build();

        $this->artifact_links_usage_dao
            ->expects($this->once())
            ->method('isProjectUsingArtifactLinkTypes')
            ->willReturn(true);

        $this->dao->expects($this->once())->method('changeTrackerHierarchy');
        $this->dao->expects($this->never())->method('updateChildren');

        $this->project_history_dao->expects($this->once())->method('addHistory');

        try {
            $this->updateWithNoTrigger($request);
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection) {
        }
    }

    public function testItDoesUpdateHierarchyIfItIsNotPossible(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('children', ['147'])
            ->build();

        $this->event_manager = EventDispatcherStub::withCallback(static function (TrackerHierarchyUpdateEvent $event) {
            $event->setHierarchyCannotBeUpdated();
            $event->setErrorMessage('You cannot.');
            return $event;
        });

        $this->dao->expects($this->never())->method('changeTrackerHierarchy');
        $this->dao->expects($this->never())->method('updateChildren');

        try {
            $this->updateWithNoTrigger($request);
            $this->fail('Should have been redirected');
        } catch (LayoutInspectorRedirection) {
        }
        self::assertSame([
            ['level' => \Feedback::ERROR, 'message' => 'You cannot.'],
        ], $this->layout_inspector->getFeedback());
    }

    public function testItCreatesHierarchyFromXMLProjectImportProcess(): void
    {
        $request = HTTPRequestBuilder::get()
            ->build();

        $mapping = [111, 222, 333, 444];
        $this->dao->expects($this->once())->method('updateChildren');

        $this->buildController($request)->updateFromXmlProjectImportProcess($mapping);
    }
}
