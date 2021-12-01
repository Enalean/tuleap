<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\CrossReference;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\TrackerColor;

class CrossReferenceArtifactOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CrossReferenceArtifactOrganizer
     */
    private $organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);

        $this->organizer = new CrossReferenceArtifactOrganizer(
            $this->artifact_factory,
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user = Mockery::mock(PFUser::class);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    ],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeArtifactCrossReferencesIfArtifactCannotBeFound(): void
    {
        $user = Mockery::mock(PFUser::class);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_tracker_artifact')
            ->withValue('123')
            ->build();

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->andReturnNull();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function testItMovesArtifactCrossReferenceToAnUnlabelledSectionWithATitleBadge(): void
    {
        $user = Mockery::mock(PFUser::class);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_tracker_artifact')
            ->withValue('123')
            ->build();

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->andReturn(
                Mockery::mock(Artifact::class)
                    ->shouldReceive(
                        [
                            'getXRef'    => 'bug #123',
                            'getTitle'   => 'Issue on submit button. Please fix ASAP!',
                            'getTracker' => Mockery::mock(\Tracker::class)
                                ->shouldReceive(
                                    [
                                        'getColor' => TrackerColor::fromName('fiesta-red'),
                                    ]
                                )->getMock(),
                        ]
                    )->getMock(),
            );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(
                    function (CrossReferencePresenter $presenter) {
                        return $presenter->id === 1
                            && $presenter->title === 'Issue on submit button. Please fix ASAP!'
                            && $presenter->title_badge->label === 'bug #123'
                            && $presenter->title_badge->color === 'fiesta-red';
                    }
                ),
                ''
            )
            ->once();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->never();

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }

    public function testItMovesArtifactCrossReferenceWithEmptyStringInsteadOfNullTitle(): void
    {
        $user = Mockery::mock(PFUser::class);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_tracker_artifact')
            ->withValue('123')
            ->build();

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->with($user, 123)
            ->andReturn(
                Mockery::mock(Artifact::class)
                    ->shouldReceive(
                        [
                            'getXRef'    => 'bug #123',
                            'getTitle'   => null,
                            'getTracker' => Mockery::mock(\Tracker::class)
                                ->shouldReceive(
                                    [
                                        'getColor' => TrackerColor::fromName('fiesta-red'),
                                    ]
                                )->getMock(),
                        ]
                    )->getMock(),
            );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(
                    function (CrossReferencePresenter $presenter) {
                        return $presenter->id === 1
                            && $presenter->title === ''
                            && $presenter->title_badge->label === 'bug #123'
                            && $presenter->title_badge->color === 'fiesta-red';
                    }
                ),
                ''
            )
            ->once();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->never();

        $this->organizer->organizeArtifactReferences($by_nature_organizer);
    }
}
