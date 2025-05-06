<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Codendi_Request;
use EventManager;
use Feedback;
use PFUser;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tracker;
use Tracker_Artifact_Redirect;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElementFactory;
use Tracker_HierarchyFactory;
use Tracker_IDisplayTrackerLayout;
use Tuleap\GlobalResponseMock;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainerBuilder;
use Tuleap\Tracker\Artifact\Link\ArtifactReverseLinksUpdater;
use Tuleap\Tracker\Artifact\Link\ForwardLinkProxy;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CreateNewChangesetStub;
use Tuleap\Tracker\Test\Stub\Permission\TrackersPermissionsPassthroughRetriever;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveReverseLinksStub;
use Tuleap\Tracker\Test\Stub\RetrieveViewableArtifactStub;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;
use function Psl\Json\encode as psl_json_encode;

#[DisableReturnValueGenerationForTestDoubles]
final class UpdateArtifactActionTest extends TestCase
{
    use GlobalResponseMock;

    private const ARTIFACT_ID = 123;
    private const TRACKER_ID  = 101;

    private Artifact&MockObject $task;
    private Artifact&MockObject $user_story;
    private Tracker_FormElement_Field_Computed&MockObject $computed_field;
    private Tracker_FormElement_Field_Computed&MockObject $us_computed_field;
    private ?string $old_request_with = null;
    private TypeIsChildLinkRetriever&MockObject $artifact_retriever;
    private Tracker_IDisplayTrackerLayout&MockObject $layout;
    private Codendi_Request $request;
    private PFUser $user;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;
    private EventManager&MockObject $event_manager;
    private UpdateArtifactAction $action;
    private HiddenFieldsetsDetector&MockObject $hidden_fieldsets_detector;
    private ArtifactReverseLinksUpdater $artifact_updater;

    protected function setUp(): void
    {
        $tracker_user_story_id = 103;
        $tracker               = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('augmentDataFromRequest');
        $tracker->method('getItemName');
        $tracker->method('hasFormElementWithNameAndType');
        $this->layout              = $this->createMock(Tracker_IDisplayTrackerLayout::class);
        $this->request             = new Codendi_Request(['func' => 'artifact-update'], $this->createMock(ProjectManager::class));
        $this->user                = UserTestBuilder::buildWithDefaults();
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->hierarchy_factory   = $this->createMock(Tracker_HierarchyFactory::class);
        $this->computed_field      = $this->createMock(Tracker_FormElement_Field_Computed::class);
        $this->us_computed_field   = $this->createMock(Tracker_FormElement_Field_Computed::class);
        $this->user_story          = $this->createMock(Artifact::class);
        $tracker_user_story        = $this->createMock(Tracker::class);
        $tracker_user_story->method('getId')->willReturn($tracker_user_story_id);
        $tracker_user_story->method('hasFormElementWithNameAndType');

        $this->user_story->method('getTrackerId')->willReturn($tracker_user_story_id);
        $this->user_story->method('getTracker')->willReturn($tracker_user_story);
        $this->user_story->method('getId')->willReturn(107);

        $this->task = $this->createPartialMock(Artifact::class, ['createNewChangeset']);
        $this->task->setHierarchyFactory($this->hierarchy_factory);
        $this->task->setTracker($tracker);
        $this->task->setFormElementFactory($this->formelement_factory);
        $this->task->setId(self::ARTIFACT_ID);

        $this->computed_field->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $this->us_computed_field->method('fetchCardValue')->with($this->user_story)->willReturn(23);
        $this->us_computed_field->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);

        $this->event_manager             = $this->createMock(EventManager::class);
        $this->artifact_retriever        = $this->createMock(TypeIsChildLinkRetriever::class);
        $this->hidden_fieldsets_detector = $this->createMock(HiddenFieldsetsDetector::class);
        $this->artifact_updater          = new ArtifactReverseLinksUpdater(
            RetrieveReverseLinksStub::withoutLinks(),
            new ReverseLinksToNewChangesetsConverter($this->formelement_factory, RetrieveViewableArtifactStub::withNoArtifact()),
            CreateNewChangesetStub::withNullReturnChangeset(),
        );

        $this->action = new UpdateArtifactAction(
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $this->createMock(VisitRecorder::class),
            $this->hidden_fieldsets_detector,
            $this->artifact_updater,
            new TrackersPermissionsPassthroughRetriever(),
            new ChangesetValuesContainerBuilder(
                $this->formelement_factory,
                ValinorMapperBuilderFactory::mapperBuilder()->allowPermissiveTypes()->mapper(),
                new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withLinks(new CollectionOfForwardLinks([
                    ForwardLinkProxy::buildFromData(1, '_is_child'),
                    ForwardLinkProxy::buildFromData(2, ''),
                ]))),
                new NewArtifactLinkInitialChangesetValueBuilder(),
            ),
        );
    }

    private function setUpAjaxRequestHeaders(): void
    {
        $this->old_request_with           = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
    }

    #[After]
    protected function restoreAjaxRequestHeaders(): void
    {
        if ($this->old_request_with === null) {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        } else {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->old_request_with;
        }
    }

    public function testItDoesNotSendAnythingIfNoRemainingEffortFieldIsDefinedOnTask(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $this->formelement_factory->method('getComputableFieldByNameForUser')->with(self::TRACKER_ID, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->willReturn(null);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);

        $expected = [];
        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendsParentsRemainingEffortEvenIfTaskDontHaveOne(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($this->user_story);
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => null,
                103              => $this->us_computed_field,
            }
        );
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);

        $user_story_id = $this->user_story->getId();
        $expected      = [$user_story_id => ['remaining_effort' => 23]];
        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNotSendParentWhenParentHasNoRemainingEffortField(): void
    {
        $this->setUpAjaxRequestHeaders();
        $tracker_user_story = TrackerTestBuilder::aTracker()->withId(110)->build();
        $user_story         = ArtifactTestBuilder::anArtifact(111)->inTracker($tracker_user_story)->build();

        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($user_story);
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturn(null);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);

        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with([]);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendTheAutocomputedValueOfTheArtifact(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->with(self::TRACKER_ID, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->willReturn($this->computed_field);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('augmentDataFromRequest');
        $task = $this->createMock(Artifact::class);
        $task->method('getId')->willReturn(self::ARTIFACT_ID);
        $task->method('getTracker')->willReturn($tracker);
        $task->method('validateCommentFormat')->willReturn(CommentFormatIdentifier::COMMONMARK);
        $task->method('fetchDirectLinkToArtifact');
        $task->method('summonArtifactRedirectors');
        $task->method('getParent');
        $visit_recorder = $this->createMock(VisitRecorder::class);

        $action = new UpdateArtifactAction(
            $task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector,
            $this->artifact_updater,
            new TrackersPermissionsPassthroughRetriever(),
            new ChangesetValuesContainerBuilder(
                $this->formelement_factory,
                ValinorMapperBuilderFactory::mapperBuilder()->allowPermissiveTypes()->mapper(),
                new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withoutLinks()),
                new NewArtifactLinkInitialChangesetValueBuilder(),
            ),
        );

        $this->computed_field->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $task->method('getTracker')->willReturn($tracker);
        $this->computed_field->method('fetchCardValue')->with($task)->willReturn(42);
        $tracker->method('hasFormElementWithNameAndType')->willReturn(true);
        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(true);

        $expected = [
            self::ARTIFACT_ID => ['remaining_effort' => '42 (autocomputed)'],
        ];
        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendsTheRemainingEffortOfTheArtifactAndItsParent(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => $this->computed_field,
                103              => $this->us_computed_field,
            }
        );
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(false);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($this->user_story);
        $this->computed_field->method('fetchCardValue')->with($this->task)->willReturn(42);

        $user_story_id = $this->user_story->getId();
        $expected      = [
            self::ARTIFACT_ID => ['remaining_effort' => 42],
            $user_story_id    => ['remaining_effort' => 23],
        ];
        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNotSendParentsRemainingEffortWhenThereIsNoParent(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->with(self::TRACKER_ID, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->willReturn($this->computed_field);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(false);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $this->computed_field->method('fetchCardValue')->with($this->task)->willReturn(42);

        $expected = [
            self::ARTIFACT_ID => ['remaining_effort' => 42],
        ];
        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testSendCardInfoOnUpdateWithRemainingEffortItDoesNotSendParentWhenParentHasNoRemainingEffortField(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => $this->computed_field,
                110              => null,
            }
        );
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $tracker_user_story = TrackerTestBuilder::aTracker()->withId(110)->build();
        $user_story         = ArtifactTestBuilder::anArtifact(111)->inTracker($tracker_user_story)->build();

        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(false);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($user_story);
        $this->computed_field->method('fetchCardValue')->with($this->task)->willReturn(42);

        $expected = [
            self::ARTIFACT_ID => ['remaining_effort' => 42],
        ];
        $GLOBALS['Response']->expects($this->once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    private function getProcessAndCaptureOutput(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $user): string
    {
        ob_start();
        $this->action->process($layout, $request, $user);
        return (string) ob_get_clean();
    }

    public function testItCreatesAChangeset(): void
    {
        $this->expectNotToPerformAssertions();
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], $this->createMock(ProjectManager::class));

        $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
    }

    public function testItReturnsTheScriptTagIfRequestIsFromOverlay(): void
    {
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($this->user_story);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], $this->createMock(ProjectManager::class));

        $from_overlay = $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
        $expected     = '<script type="text/javascript" nonce="">window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition(' . $this->task->getId() . ');</script>';
        self::assertSame($expected, $from_overlay);
    }

    public function testItDoesntReturnScriptWhenInAjax(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => null,
                103              => $this->us_computed_field,
            }
        );
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn(null);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], $this->createMock(ProjectManager::class));

        $from_overlay = $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
        $this->assertStringNotContainsStringIgnoringCase('<script>', $from_overlay);
    }

    private function getRedirectUrlFor(array $request_data): Tracker_Artifact_Redirect
    {
        $request = new Codendi_Request($request_data, $this->createMock(ProjectManager::class));
        $action  = new UpdateArtifactAction(
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $this->createMock(VisitRecorder::class),
            $this->hidden_fieldsets_detector,
            $this->artifact_updater,
            new TrackersPermissionsPassthroughRetriever(),
            new ChangesetValuesContainerBuilder(
                $this->formelement_factory,
                ValinorMapperBuilderFactory::mapperBuilder()->allowPermissiveTypes()->mapper(),
                new NewArtifactLinkChangesetValueBuilder(RetrieveForwardLinksStub::withoutLinks()),
                new NewArtifactLinkInitialChangesetValueBuilder(),
            ),
        );
        return $action->getRedirectUrlAfterArtifactUpdate($request);
    }

    public function testItRedirectsToTheTrackerHomePageByDefault(): void
    {
        $request_data = [];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . '/?tracker=' . self::TRACKER_ID, $redirect_uri->toUrl());
    }

    public function testItStaysOnTheCurrentArtifactWhenSubmitAndStayIsSpecified(): void
    {
        $request_data = ['submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . '/?aid=' . self::ARTIFACT_ID, $redirect_uri->toUrl());
    }

    public function testItReturnsToThePreviousArtifactWhenFromAidIsGiven(): void
    {
        $from_aid     = 33;
        $request_data = ['from_aid' => $from_aid];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . "/?aid=$from_aid", $redirect_uri->toUrl());
    }

    private function assertURIHasArgument(string $url, string $argument, string $argument_value): void
    {
        $query_string = parse_url($url, PHP_URL_QUERY);
        parse_str((string) $query_string, $args);
        $this->assertTrue(isset($args[$argument]));
        $this->assertEquals($argument_value, $args[$argument]);
    }

    public function testSubmitAndStayHasPrecedenceOverFromAid(): void
    {
        $from_aid     = 33;
        $request_data = [
            'from_aid'        => $from_aid,
            'submit_and_stay' => true,
        ];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'aid', (string) self::ARTIFACT_ID);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'from_aid', (string) $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOverReturnToAid(): void
    {
        $request_data = ['submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'aid', (string) self::ARTIFACT_ID);
    }

    public function testItCanEditLinks(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(645)
            ->withSpecificProperty('can_edit_reverse_links', ['value' => 1])
            ->build();
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn($field);

        $request = new Codendi_Request([
            'func'     => 'artifact-update',
            'artifact' => [
                645 => psl_json_encode([
                    'field_id'  => 645,
                    'all_links' => [],
                ]),
            ],
        ], $this->createMock(ProjectManager::class));

        $GLOBALS['Response']->expects($this->once())->method('redirect')->with('/plugins/tracker/?tracker=101');
        $GLOBALS['Response']->method('addFeedback')->with(Feedback::INFO, self::stringContains('Successfully Updated'));

        $this->action->process($this->layout, $request, $this->user);
    }
}
