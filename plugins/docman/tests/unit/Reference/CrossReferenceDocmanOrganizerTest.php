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

namespace Tuleap\Docman\Reference;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;

class CrossReferenceDocmanOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var CrossReferenceDocmanOrganizer
     */
    private $organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DocumentFromReferenceValueFinder
     */
    private $finder;

    protected function setUp(): void
    {
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->finder          = Mockery::mock(DocumentFromReferenceValueFinder::class);

        $this->organizer = new CrossReferenceDocmanOrganizer(
            $this->project_manager,
            $this->finder,
            new DocumentIconPresenterBuilder()
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
                        new CrossReferencePresenter(
                            1,
                            "git",
                            "another_title",
                            "url",
                            "delete_url",
                            1,
                            "whatever",
                            null,
                            [],
                            null,
                        ),
                    ],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeDocumentReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeArtifactCrossReferencesIfArtifactCannotBeFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();
        $a_ref = new CrossReferencePresenter(
            1,
            "document",
            "doc #123",
            "url",
            "delete_url",
            1,
            '123',
            null,
            [],
            null,
        );

        $this->finder
            ->shouldReceive('findItem')
            ->with($project, $user, '123')
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

        $this->organizer->organizeDocumentReferences($by_nature_organizer);
    }

    public function testItMovesArtifactCrossReferenceToAnUnlabelledSectionWithATitleBadge(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $a_ref = new CrossReferencePresenter(
            1,
            "document",
            "doc #123",
            "url",
            "delete_url",
            1,
            '123',
            null,
            [],
            null,
        );

        $this->finder
            ->shouldReceive('findItem')
            ->with($project, $user, '123')
            ->andReturn(new \Docman_Folder(['title' => 'Lorem ipsum']));

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
                            && $presenter->title === 'Lorem ipsum'
                            && $presenter->title_badge->icon === 'fa fa-folder'
                            && $presenter->title_badge->color === 'inca-silver';
                    }
                ),
                ''
            )
            ->once();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->never();

        $this->organizer->organizeDocumentReferences($by_nature_organizer);
    }
}
