<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_ArtifactFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps, PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private Tracker_ArtifactDao&MockObject $dao;

    /** @var Tracker_ArtifactFactory */
    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->dao              = $this->createMock(\Tracker_ArtifactDao::class);
        $this->artifact_factory = $this->createPartialMock(\Tracker_ArtifactFactory::class, ['getDao']);
        $this->artifact_factory->method('getDao')->willReturn($this->dao);
    }

    public function testItFetchArtifactsTitlesFromDb(): void
    {
        $art12 = ArtifactTestBuilder::anArtifact(12)->build();
        $art30 = ArtifactTestBuilder::anArtifact(30)->build();

        $artifacts = [$art12, $art30];

        $this->dao->expects($this->once())->method('getTitles')->with([12, 30])->willReturn(\TestHelper::emptyDar());

        $this->artifact_factory->setTitles($artifacts);
    }

    public function testItSetTheTitlesToTheArtifact(): void
    {
        $art24 = ArtifactTestBuilder::anArtifact(24)->build();

        $artifacts = [$art24];

        $this->dao->method('getTitles')->willReturn(\TestHelper::arrayToDar(['id' => 24, 'title' => 'Zoum']));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEquals('Zoum', $art24->getTitle());
    }

    public function testItSetTheTitlesWhenThereAreSeveralArtifacts(): void
    {
        $art24 = ArtifactTestBuilder::anArtifact(24)->build();
        $art32 = ArtifactTestBuilder::anArtifact(32)->build();

        $artifacts = [$art24, $art32];

        $this->dao->method('getTitles')->willReturn(\TestHelper::arrayToDar(['id' => 24, 'title' => 'Zoum'], ['id' => 32, 'title' => 'Zen']));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEquals('Zoum', $art24->getTitle());
        $this->assertEquals('Zen', $art32->getTitle());
    }

    public function testItSetTheTitlesWhenThereAreSeveralTimeTheSameArtifact(): void
    {
        $art24_1 = ArtifactTestBuilder::anArtifact(24)->build();
        $art24_2 = ArtifactTestBuilder::anArtifact(24)->build();

        $artifacts = [$art24_1, $art24_2];

        $this->dao->method('getTitles')->willReturn(\TestHelper::arrayToDar(['id' => 24, 'title' => 'Zoum']));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEquals('Zoum', $art24_1->getTitle());
        $this->assertEquals('Zoum', $art24_2->getTitle());
    }

    public function testItGetFirstParentFoundOfArtifact(): void
    {
        $artifact_with_multiple_parents_id = 1;
        $artifact_with_single_parents_id   = 2;
        $this->dao->method('getParents')
            ->willReturn(
                \TestHelper::arrayToDar(
                    [
                        'child_id'                 => $artifact_with_multiple_parents_id,
                        'id'                       => 10,
                        'tracker_id'               => 1,
                        'submitted_by'             => 123,
                        'submitted_on'             => 123456789,
                        'use_artifact_permissions' => false,
                    ],
                    [
                        'child_id'                 => $artifact_with_multiple_parents_id,
                        'id'                       => 20,
                        'tracker_id'               => 1,
                        'submitted_by'             => 123,
                        'submitted_on'             => 123456789,
                        'use_artifact_permissions' => false,
                    ],
                    [
                        'child_id'                 => $artifact_with_single_parents_id,
                        'id'                       => 100,
                        'tracker_id'               => 1,
                        'submitted_by'             => 123,
                        'submitted_on'             => 123456789,
                        'use_artifact_permissions' => false,
                    ]
                ),
            );

        $parents = $this->artifact_factory->getParents(
            [
                $artifact_with_multiple_parents_id,
                $artifact_with_single_parents_id,
            ]
        );

        self::assertSame(10, $parents[$artifact_with_multiple_parents_id]->getId());
        self::assertSame(100, $parents[$artifact_with_single_parents_id]->getId());
    }
}
