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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Action_CreateArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $tracker;
    private $formelement_factory;
    private $action;
    private $request;
    /**
     * @var int
     */
    private $tracker_id;
    /**
     * @var PFUser
     */
    private $current_user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $new_artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $parent_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_ArtifactLink
     */
    private $parent_art_link_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_ArtifactLink
     */
    private $art_link_field;
    /**
     * @var Tracker_Artifact_Redirect
     */
    private $redirect;

    protected function setUp(): void
    {
        $event_manager = \Mockery::spy(\EventManager::class);
        EventManager::setInstance($event_manager);

        $this->tracker             = \Mockery::spy(\Tracker::class);
        $artifact_factory          = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->request             = \Mockery::spy(\Codendi_Request::class);


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
        $this->new_artifact = Mockery::spy(Artifact::class);
        $this->new_artifact->shouldReceive('getId')->andReturn(123);

        $this->tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $this->parent_tracker = Mockery::spy(Tracker::class);
        $this->parent_tracker->shouldReceive('getId')->andReturn(666);
        $this->parent_art_link_field = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->art_link_field        = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);

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
        $this->assertUriHasArgument($redirect_uri->toUrl(), "func", 'new-artifact');
    }

    private function getRedirectUrlFor(array $request_data, ?int $tracker_id, ?int $artifact_id): Tracker_Artifact_Redirect
    {
        $request = new Codendi_Request($request_data);
        return $this->action->redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id);
    }

    public function testItDoesRedirectWhenPackageIsComplete(): void
    {
        $this->tracker->shouldReceive('getParent')->andReturns($this->parent_tracker);
        $this->formelement_factory->shouldReceive('getAnArtifactLinkField')->with($this->current_user, $this->parent_tracker)->andReturns($this->parent_art_link_field);
        $this->formelement_factory->shouldReceive('getAnArtifactLinkField')->with($this->current_user, $this->tracker)->andReturns($this->art_link_field);
        $this->art_link_field->shouldReceive('getId')->andReturns(333);
        $this->request->shouldReceive('get')->with('artifact')->andReturns([
            333 => [
                'parent' => [(string) Tracker_FormElement_Field_ArtifactLink::CREATE_NEW_PARENT_VALUE],
            ],
        ]);
        $this->new_artifact->shouldReceive('getAllAncestors')->with($this->current_user)->andReturns([]);

        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect, $this->request);
        self::assertNotEmpty($this->redirect->query_parameters);
        self::assertArrayHasKey("func", $this->redirect->query_parameters);
        self::assertArrayHasKey("tracker", $this->redirect->query_parameters);
    }

    public function testItDoesntRedirectWhenNewArtifactAlreadyHasAParent(): void
    {
        $this->new_artifact->shouldReceive('getAllAncestors')->andReturns([Mockery::spy(Artifact::class)]);

        $this->tracker->shouldReceive('getParent')->andReturns($this->parent_tracker);
        $this->formelement_factory->shouldReceive('getAnArtifactLinkField')->andReturns($this->parent_art_link_field);

        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect, $this->request);
        $this->assertEmpty($this->redirect->query_parameters);
    }

    public function testItDoesntRedirectIfThereAreNoHierarchy(): void
    {
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
            ->expects(self::once())
            ->method('linkArtifact')
            ->with(
                $new_artifact_id,
                $current_user,
                \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD
            );

        $artifact_factory->expects(self::once())
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
