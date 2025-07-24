<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tracker_REST_TrackerRestBuilder;
use Tracker_RulesManager;
use Tuleap\Color\ColorName;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Container\FieldsExtractor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Hierarchy\SearchParentTrackerStub;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnTrackersStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use WorkflowWithoutTransition;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerRestBuilderTest extends TestCase
{
    private const PARENT_TRACKER_ID = 264;
    private const TRACKER_ID        = 720;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private SearchParentTrackerStub $search_parent_tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->form_element_factory  = $this->createMock(Tracker_FormElementFactory::class);
        $this->search_parent_tracker = SearchParentTrackerStub::withNoParent();
    }

    private function getTrackerInTrackerContext(): CompleteTrackerRepresentation
    {
        $project = ProjectTestBuilder::aProject()->build();

        $parent_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::PARENT_TRACKER_ID)
            ->withProject($project)
            ->build();

        $ugroup_manager                = $this->createMock(\UGroupManager::class);
        $permissions_functions_wrapper = $this->createMock(PermissionsFunctionsWrapper::class);
        $transition_retriever          = new TransitionRetriever(
            new StateFactory(
                $this->createMock(\TransitionFactory::class),
                $this->createMock(SimpleWorkflowDao::class),
            ),
            new TransitionExtractor()
        );
        $frozen_field_detector         = new FrozenFieldDetector(
            $transition_retriever,
            new FrozenFieldsRetriever($this->createMock(FrozenFieldsDao::class), $this->form_element_factory,)
        );

        $semantic_manager = $this->createMock(\Tuleap\Tracker\Semantic\TrackerSemanticManager::class);
        $semantic_manager->method('exportToREST')->willReturn([]);
        $builder = new Tracker_REST_TrackerRestBuilder(
            $this->form_element_factory,
            new FormElementRepresentationsBuilder(
                $this->form_element_factory,
                new PermissionsExporter($frozen_field_detector),
                new HiddenFieldsetChecker(
                    new HiddenFieldsetsDetector(
                        $transition_retriever,
                        new HiddenFieldsetsRetriever(
                            $this->createMock(HiddenFieldsetsDao::class),
                            $this->form_element_factory,
                        ),
                        $this->form_element_factory
                    ),
                    new FieldsExtractor()
                ),
                new PermissionsForGroupsBuilder(
                    $ugroup_manager,
                    $frozen_field_detector,
                    $permissions_functions_wrapper,
                ),
                new TypePresenterFactory(
                    $this->createMock(TypeDao::class),
                    $this->createMock(ArtifactLinksUsageDao::class),
                )
            ),
            new PermissionsRepresentationBuilder($ugroup_manager, $permissions_functions_wrapper),
            new WorkflowRestBuilder(),
            static fn() => $semantic_manager,
            new ParentInHierarchyRetriever(
                $this->search_parent_tracker,
                RetrieveTrackerStub::withTracker($parent_tracker),
            ),
            RetrieveUserPermissionOnTrackersStub::build()->withPermissionOn(
                [self::PARENT_TRACKER_ID],
                TrackerPermissionType::PERMISSION_VIEW
            )
        );

        $workflow = $this->createConfiguredMock(WorkflowWithoutTransition::class, [
            'getFieldId' => 0,
            'getField' => null,
            'isLegacy' => true,
            'isAdvanced' => false,
            'getTrackerId' => self::TRACKER_ID,
            'getTransitions' => [],
        ]);

        $rules_manager = $this->createMock(Tracker_RulesManager::class);
        $rules_manager->method('getAllDateRulesByTrackerId')->willReturn([]);
        $rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn([]);
        $workflow->method('getGlobalRulesManager')->willReturn($rules_manager);

        $tracker = $this->createConfiguredMock(\Tuleap\Tracker\Tracker::class, [
            'getId' => self::TRACKER_ID,
            'getUri' => '/plugins/tracker/?tracker=' . self::TRACKER_ID,
            'getDescription' => 'Tracks User Stories for developers',
            'getName' => 'User Stories',
            'getItemName' => 'story',
            'getColor' => ColorName::DEEP_BLUE,
            'getWorkflow' => $workflow,
            'userIsAdmin' => false,
            'getProject' => $project,
            'isNotificationStopped' => false,
        ]);

        $user = UserTestBuilder::buildWithDefaults();

        return $builder->getTrackerRepresentationInTrackerContext($user, $tracker);
    }

    public function testItBuildsTrackerRepresentation(): void
    {
        $this->form_element_factory->method('getAllUsedFormElementOfAnyTypesForTracker')
            ->willReturn([]);
        $this->form_element_factory->method('getUsedFormElementForTracker')
            ->willReturn([]);
        $this->search_parent_tracker = SearchParentTrackerStub::withParentTracker(self::PARENT_TRACKER_ID);

        $representation = $this->getTrackerInTrackerContext();
        self::assertSame(self::TRACKER_ID, $representation->id);
        self::assertSame(self::PARENT_TRACKER_ID, $representation->parent?->id);
    }
}
