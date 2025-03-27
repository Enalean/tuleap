<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Action_CreateArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker&MockObject $tracker;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Tracker_Action_CreateArtifact $action;
    private Codendi_Request&MockObject $request;
    private int $tracker_id;
    private PFUser $current_user;
    private Artifact&MockObject $new_artifact;
    private Tracker $parent_tracker;
    private Tracker_FormElement_Field_ArtifactLink $parent_art_link_field;
    private Tracker_FormElement_Field_ArtifactLink $art_link_field;
    private Tracker_Artifact_Redirect $redirect;

    protected function setUp(): void
    {
        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->method('processEvent');
        EventManager::setInstance($event_manager);

        $this->tracker             = $this->createMock(\Tracker::class);
        $artifact_factory          = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->request             = $this->createMock(\Codendi_Request::class);


        $this->action = new class (
            $this->tracker,
            $this->createMock(\Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator::class),
            $artifact_factory,
            $this->formelement_factory
        ) extends Tracker_Action_CreateArtifact
        {
            public function redirectToParentCreationIfNeeded(Artifact $artifact, PFUser $current_user, Tracker_Artifact_Redirect $redirect, Codendi_Request $request): void
            {
                parent::redirectToParentCreationIfNeeded($artifact, $current_user, $redirect, $request);
            }

            public function redirectUrlAfterArtifactSubmission(Codendi_Request $request, $tracker_id, $artifact_id): Tracker_Artifact_Redirect
            {
                return parent::redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id);
            }
        };

        $this->tracker_id   = 999;
        $this->current_user = new PFUser(['language_id' => 'en']);
        $this->new_artifact = $this->createMock(Artifact::class);
        $this->new_artifact->method('getId')->willReturn(123);

        $this->tracker->method('getId')->willReturn($this->tracker_id);

        $this->parent_tracker        = TrackerTestBuilder::aTracker()->withId(666)->build();
        $this->parent_art_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1001)->build();
        $this->art_link_field        = ArtifactLinkFieldBuilder::anArtifactLinkField(333)->build();

        $this->redirect = new Tracker_Artifact_Redirect();
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
    }

    public function testItRedirectsToTheTrackerHomePageByDefault(): void
    {
        $request_data = [];
        $tracker_id   = 20;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, null);
        $this->assertEquals(TRACKER_BASE_URL . "/?tracker=$tracker_id", $redirect_uri->toUrl());
    }

    public function testItStaysOnTheCurrentArtifactWhenSubmitAndStayIsSpecified(): void
    {
        $request_data = ['submit_and_stay' => true];
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertEquals(TRACKER_BASE_URL . "/?aid=$artifact_id", $redirect_uri->toUrl());
    }

    private function assertURIHasArgument(string $url, string $argument, string $argument_value): void
    {
        $query_string = parse_url($url, PHP_URL_QUERY);
        parse_str($query_string, $args);
        $this->assertTrue(isset($args[$argument]));
        $this->assertEquals($argument_value, $args[$argument]);
    }

    public function testItRedirectsToNewArtifactCreationWhenSubmitAndContinueIsSpecified(): void
    {
        $request_data = ['submit_and_continue' => true];
        $tracker_id   = 73;
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertStringStartsWith(TRACKER_BASE_URL, $redirect_uri->toUrl());
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'func', 'new-artifact');
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'tracker', (string) $tracker_id);
    }

    public function testSubmitAndContinue(): void
    {
        $request_data = ['submit_and_continue' => true];
        $tracker_id   = 73;
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'func', 'new-artifact');
    }

    private function getRedirectUrlFor(array $request_data, ?int $tracker_id, ?int $artifact_id): Tracker_Artifact_Redirect
    {
        $request = new Codendi_Request($request_data);
        return $this->action->redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id);
    }

    public function testItDoesRedirectWhenPackageIsComplete(): void
    {
        $this->tracker->method('getParent')->willReturn($this->parent_tracker);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturnCallback(
            fn (PFUser $user, Tracker $tracker) => match ($tracker) {
                $this->parent_tracker => $this->parent_art_link_field,
                $this->tracker => $this->art_link_field,
            }
        );
        $this->request->method('get')->with('artifact')->willReturn([
            333 => [
                'parent' => [(string) Tracker_FormElement_Field_ArtifactLink::CREATE_NEW_PARENT_VALUE],
            ],
        ]);
        $this->new_artifact->method('getAllAncestors')->with($this->current_user)->willReturn([]);

        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect, $this->request);
        self::assertNotEmpty($this->redirect->query_parameters);
        self::assertArrayHasKey('func', $this->redirect->query_parameters);
        self::assertArrayHasKey('tracker', $this->redirect->query_parameters);
    }

    public function testItDoesntRedirectWhenNewArtifactAlreadyHasAParent(): void
    {
        $this->new_artifact->method('getAllAncestors')->willReturn([$this->createMock(Artifact::class)]);

        $this->tracker->method('getParent')->willReturn($this->parent_tracker);
        $this->formelement_factory->method('getAnArtifactLinkField')->willReturn($this->parent_art_link_field);

        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect, $this->request);
        $this->assertEmpty($this->redirect->query_parameters);
    }

    public function testItDoesntRedirectIfThereAreNoHierarchy(): void
    {
        $this->tracker->method('getParent')->willReturn(null);
        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect, $this->request);
        $this->assertEmpty($this->redirect->query_parameters);
    }

    public function testItLinksToTheTargetArtifactPostCreationWithTheGivenType(): void
    {
        $target_artifact_id = 1280;
        $new_artifact_id    = 1281;
        $current_user       = UserTestBuilder::buildWithDefaults();
        $target_artifact    = $this->createMock(Artifact::class);
        $new_artifact       = ArtifactTestBuilder::anArtifact($new_artifact_id)->build();
        $artifact_factory   = $this->createMock(\Tracker_ArtifactFactory::class);

        $target_artifact
            ->expects($this->once())
            ->method('linkArtifact')
            ->with(
                $new_artifact_id,
                $current_user,
                \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD
            );

        $artifact_factory->expects($this->once())
            ->method('getArtifactById')
            ->with($target_artifact_id)
            ->willReturn($target_artifact);

        $target_artifact->method('getId')->willReturn($target_artifact_id);

        $action = new class (
            TrackerTestBuilder::aTracker()->build(),
            $this->createMock(\Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator::class),
            $artifact_factory,
            $this->createMock(\Tracker_FormElementFactory::class)
        ) extends Tracker_Action_CreateArtifact
        {
            public function associateImmediatelyIfNeeded(Artifact $new_artifact, \Codendi_Request $request, PFUser $current_user): void
            {
                parent::associateImmediatelyIfNeeded($new_artifact, $request, $current_user);
            }
        };

        $action->associateImmediatelyIfNeeded(
            $new_artifact,
            new \Codendi_Request(
                [
                    'link-artifact-id' => (string) $target_artifact_id,
                    'link-type'        => \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD,
                    'immediate'        => 'true',
                ],
                $this->createMock(\ProjectManager::class)
            ),
            $current_user
        );
    }
}
