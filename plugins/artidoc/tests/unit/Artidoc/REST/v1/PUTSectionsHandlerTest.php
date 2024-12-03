<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Stubs\BuildRequiredArtifactInformationStub;
use Tuleap\Artidoc\Stubs\Document\SaveSectionsStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class PUTSectionsHandlerTest extends TestCase
{
    private const PROJECT_ID = 101;

    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();
    }

    public function testHappyPath(): void
    {
        $saver = SaveSectionsStub::build();

        $handler = new PUTSectionsHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            BuildRequiredArtifactInformationStub::withRequiredArtifactInformation([
                101 => RequiredArtifactInformationTestBuilder::fromArtifact(
                    ArtifactTestBuilder::anArtifact(101)->build(),
                )->build(),
                102 => RequiredArtifactInformationTestBuilder::fromArtifact(
                    ArtifactTestBuilder::anArtifact(102)->build(),
                )->build(),
            ]),
            $saver,
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertSame([102, 101], $saver->getSavedForId(1));
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $saver = SaveSectionsStub::build();

        $handler = new PUTSectionsHandler(
            RetrieveArtidocWithContextStub::withoutDocument(),
            BuildRequiredArtifactInformationStub::shouldNotBeCalled(),
            $saver,
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenTheUserCannotLoadTheDocumentComposedOfNewSections(): void
    {
        $saver = SaveSectionsStub::build();

        $handler = new PUTSectionsHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            BuildRequiredArtifactInformationStub::withoutRequiredArtifactInformation(),
            $saver,
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $saver = SaveSectionsStub::build();

        $handler = new PUTSectionsHandler(
            RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                new ArtidocWithContext(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                ),
            ),
            BuildRequiredArtifactInformationStub::shouldNotBeCalled(),
            $saver,
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }
}
