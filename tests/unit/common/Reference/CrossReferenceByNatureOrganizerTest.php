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

namespace Tuleap\Reference;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CrossReferenceByNatureOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItMovesOneCrossReferenceToASection(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "git",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [
                'git'   => new Nature('git', 'fas fa-tlp-versioning-git', 'Git'),
                'other' => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');

        self::assertEquals([$another_ref], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMoveCrossReferenceEvenIfItHasBeenPimped(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "git",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [
                'git'   => new Nature('git', 'fas fa-tlp-versioning-git', 'Git'),
                'other' => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $pimped_reference = $a_ref->withTitle("My new title", null);
        $organizer->moveCrossReferenceToSection(
            $pimped_reference,
            'cloudy/stable'
        );

        self::assertEquals([$another_ref], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$pimped_reference]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMovesTwoCrossReferencesToSameSection(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "git",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [
                'git'   => new Nature('git', 'fas fa-tlp-versioning-git', 'Git'),
                'other' => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->moveCrossReferenceToSection($another_ref, 'cloudy/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref, $another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMovesTwoCrossReferencesToDifferentSections(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "git",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [
                'git'   => new Nature('git', 'fas fa-tlp-versioning-git', 'Git'),
                'other' => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->moveCrossReferenceToSection($another_ref, 'tuleap/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                        new CrossReferenceSectionPresenter('tuleap/stable', [$another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItMovesTwoCrossReferencesToDifferentNatures(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "tracker",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [
                'git'     => new Nature('git', 'fas fa-tlp-versioning-git', 'Git'),
                'tracker' => new Nature('tracker', 'fas fa-list-ol', 'Trackers'),
                'other'   => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->moveCrossReferenceToSection($another_ref, "");

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                    ],
                ),
                new CrossReferenceNaturePresenter(
                    'Trackers',
                    'fas fa-list-ol',
                    [
                        new CrossReferenceSectionPresenter('', [$another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testItIgnoresCrossReferenceIfRequestedNatureIsNotFound(): void
    {
        $a_ref = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref],
            [
                'other' => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals([], $organizer->getNatures());
    }

    public function testItOrganiseRemainingCrossReferences(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "wiki",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [
                'git'   => new Nature('git', 'fas fa-tlp-versioning-git', 'Git'),
                'wiki'  => new Nature('wiki', 'fas fa-wiki', 'Wiki'),
                'other' => new Nature('other', '', 'Other'),
            ],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->moveCrossReferenceToSection($a_ref, 'cloudy/stable');
        $organizer->organizeRemainingCrossReferences();

        self::assertEquals([], $organizer->getCrossReferencePresenters());
        self::assertEquals(
            [
                new CrossReferenceNaturePresenter(
                    'Git',
                    'fas fa-tlp-versioning-git',
                    [
                        new CrossReferenceSectionPresenter('cloudy/stable', [$a_ref]),
                    ],
                ),
                new CrossReferenceNaturePresenter(
                    'Wiki',
                    'fas fa-wiki',
                    [
                        new CrossReferenceSectionPresenter('', [$another_ref]),
                    ],
                ),
            ],
            $organizer->getNatures()
        );
    }

    public function testRemoveUnreadableCrossReference(): void
    {
        $a_ref       = new CrossReferencePresenter(
            1,
            "git",
            "title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );
        $another_ref = new CrossReferencePresenter(
            2,
            "wiki",
            "another_title",
            "url",
            "delete_url",
            1,
            "whatever",
            null,
            [],
        );

        $organizer = new CrossReferenceByNatureOrganizer(
            [$a_ref, $another_ref],
            [],
            \Mockery::mock(\PFUser::class),
        );

        $organizer->removeUnreadableCrossReference($a_ref);

        self::assertEquals(
            [$another_ref],
            $organizer->getCrossReferencePresenters(),
        );
    }
}
