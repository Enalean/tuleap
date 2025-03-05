<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Artidoc\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ImportContent;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\FreetextContent;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ContentToBeCreatedBuilderTest extends TestCase
{
    public function testItThrowsWhenImportAndContentAreBothProvided(): void
    {
        $section = new POSTSectionRepresentation(
            new POSTSectionImportRepresentation(
                new POSTSectionArtifactRepresentation(101),
                Level::One->value,
            ),
            null,
            new POSTContentSectionRepresentation('title', 'description', 'freetext', [], Level::One->value),
        );
        $this->expectException(RestException::class);
        $this->expectExceptionMessage("The properties 'import' and 'content' can not be used at the same time");

        ContentToBeCreatedBuilder::buildFromRepresentation($section);
    }

    public function testItThrowsWhenImportAndContentAreBothAbsent(): void
    {
        $section = new POSTSectionRepresentation(null, null, null);
        $this->expectException(RestException::class);
        $this->expectExceptionMessage('No artifact to import or section content provided');

        ContentToBeCreatedBuilder::buildFromRepresentation($section);
    }

    public function testHappyPatchForImportedArtifact(): void
    {
        $id      = 101;
        $section = new POSTSectionRepresentation(
            new POSTSectionImportRepresentation(
                new POSTSectionArtifactRepresentation($id),
                Level::One->value,
            ),
            null,
            null,
        );

        $content_to_insert = ContentToBeCreatedBuilder::buildFromRepresentation($section);
        $content_to_insert->apply(
            function (ImportContent $import) use ($id) {
                self::assertSame($id, $import->artifact_id);

                return Result::ok($import);
            },
            function (FreetextContent $freetext) {
                self::assertNull($freetext);

                return Result::ok($freetext);
            },
            function (ArtifactContent $artifact) {
                self::assertNull($artifact);

                return Result::ok($artifact);
            },
        );
    }

    public function testHappyPatchForFreetext(): void
    {
        $section = new POSTSectionRepresentation(
            null,
            null,
            new POSTContentSectionRepresentation('title', 'description', 'freetext', [], Level::One->value),
        );

        $content_to_insert = ContentToBeCreatedBuilder::buildFromRepresentation($section);
        $content_to_insert->apply(
            function (ImportContent $import) {
                self::assertNull($import);

                return Result::ok($import);
            },
            function (FreetextContent $freetext) {
                self::assertSame('title', $freetext->title);

                return Result::ok($freetext);
            },
            function (ArtifactContent $artifact) {
                self::assertNull($artifact);

                return Result::ok($artifact);
            },
        );
    }

    public function testHappyPatchForArtifact(): void
    {
        $section = new POSTSectionRepresentation(
            null,
            null,
            new POSTContentSectionRepresentation('title', 'description', 'artifact', [], Level::One->value),
        );

        $content_to_insert = ContentToBeCreatedBuilder::buildFromRepresentation($section);
        $content_to_insert->apply(
            function (ImportContent $import) {
                self::assertNull($import);

                return Result::ok($import);
            },
            function (FreetextContent $freetext) {
                self::assertNull($freetext);

                return Result::ok($freetext);
            },
            function (ArtifactContent $artifact) {
                self::assertSame('title', $artifact->title);

                return Result::ok($artifact);
            },
        );
    }
}
