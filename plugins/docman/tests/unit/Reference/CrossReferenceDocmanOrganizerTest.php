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

use Docman_Folder;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceDocmanOrganizerTest extends TestCase
{
    private ProjectManager&MockObject $project_manager;
    private CrossReferenceDocmanOrganizer $organizer;
    private DocumentFromReferenceValueFinder&MockObject $finder;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->finder          = $this->createMock(DocumentFromReferenceValueFinder::class);

        $this->organizer = new CrossReferenceDocmanOrganizer(
            $this->project_manager,
            $this->finder,
            new DocumentIconPresenterBuilder(EventDispatcherStub::withIdentityCallback())
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            new CrossReferencePresenter(
                1,
                'git',
                'another_title',
                'url',
                'delete_url',
                1,
                'whatever',
                null,
                [],
                null,
            ),
        ]);

        $by_nature_organizer->expects($this->never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeDocumentReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeArtifactCrossReferencesIfArtifactCannotBeFound(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();

        $this->project_manager->method('getProject')->willReturn($project);
        $a_ref = new CrossReferencePresenter(
            1,
            'document',
            'doc #123',
            'url',
            'delete_url',
            1,
            '123',
            null,
            [],
            null,
        );

        $this->finder->method('findItem')->with($project, $user, '123')->willReturn(null);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);
        $by_nature_organizer->expects($this->never())->method('moveCrossReferenceToSection');
        $by_nature_organizer->expects($this->once())->method('removeUnreadableCrossReference')->with($a_ref);

        $this->organizer->organizeDocumentReferences($by_nature_organizer);
    }

    public function testItMovesArtifactCrossReferenceToAnUnlabelledSectionWithATitleBadge(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();

        $this->project_manager->method('getProject')->willReturn($project);

        $a_ref = new CrossReferencePresenter(
            1,
            'document',
            'doc #123',
            'url',
            'delete_url',
            1,
            '123',
            null,
            [],
            null,
        );

        $this->finder->method('findItem')->with($project, $user, '123')->willReturn(new Docman_Folder(['title' => 'Lorem ipsum']));

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects($this->once())
            ->method('moveCrossReferenceToSection')
            ->with(self::callback(
                static fn(CrossReferencePresenter $presenter) => (
                    $presenter->id === 1
                    && $presenter->title === 'Lorem ipsum'
                    && $presenter->title_badge->icon === 'fa fa-folder'
                    && $presenter->title_badge->color === 'inca-silver'
                )
            ), '');
        $by_nature_organizer->expects($this->never())->method('removeUnreadableCrossReference');

        $this->organizer->organizeDocumentReferences($by_nature_organizer);
    }
}
