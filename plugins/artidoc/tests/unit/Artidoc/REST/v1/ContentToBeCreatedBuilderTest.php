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
use Tuleap\Artidoc\Domain\Document\Section\Artifact\SectionContentToBeCreatedArtifact;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\SectionContentToBeCreatedFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertSame;

final class ContentToBeCreatedBuilderTest extends TestCase
{
    public function testItThrowsWhenImportAndContentAreBothProvided(): void
    {
        $section = new ArtidocSectionPOSTRepresentation(
            new POSTSectionImportRepresentation(
                new ArtidocPOSTSectionArtifactRepresentation(101),
            ),
            null,
            new POSTContentSectionRepresentation('title', 'description', 'freetext', []),
            Level::One->value,
        );
        $this->expectException(RestException::class);
        $this->expectExceptionMessage("The properties 'import' and 'content' can not be used at the same time");

        ContentToBeCreatedBuilder::buildFromRepresentation($section);
    }

    public function testItThrowsWhenImportAndContentAreBothAbsent(): void
    {
        $section = new ArtidocSectionPOSTRepresentation(null, null, null, Level::One->value);
        $this->expectException(RestException::class);
        $this->expectExceptionMessage('No artifact to import or section content provided');

        ContentToBeCreatedBuilder::buildFromRepresentation($section);
    }

    public function testHappyPatchForImportedArtifact(): void
    {
        $id      = 101;
        $section = new ArtidocSectionPOSTRepresentation(
            new POSTSectionImportRepresentation(
                new ArtidocPOSTSectionArtifactRepresentation($id),
            ),
            null,
            null,
            Level::One->value,
        );

        $content_to_insert = ContentToBeCreatedBuilder::buildFromRepresentation($section);
        $content_to_insert->apply(
            function (int $artifact_id) use ($id) {
                assertSame($id, $artifact_id);
                return Result::ok($artifact_id);
            },
            function (SectionContentToBeCreatedFreetext $freetext) {
                assertSame(null, $freetext);
                return Result::ok($freetext);
            },
            function (SectionContentToBeCreatedArtifact $artifact) {
                assertSame(null, $artifact);
                return Result::ok($artifact);
            },
        );
    }

    public function testHappyPatchForFreetext(): void
    {
        $section = new ArtidocSectionPOSTRepresentation(
            null,
            null,
            new POSTContentSectionRepresentation('title', 'description', 'freetext', []),
            Level::One->value,
        );

        $content_to_insert = ContentToBeCreatedBuilder::buildFromRepresentation($section);
        $content_to_insert->apply(
            function (int $artifact_id) {
                assertSame(null, $artifact_id);
                return Result::ok($artifact_id);
            },
            function (SectionContentToBeCreatedFreetext $freetext) {
                assertSame('title', $freetext->content->title);
                return Result::ok($freetext);
            },
            function (SectionContentToBeCreatedArtifact $artifact) {
                assertSame(null, $artifact);
                return Result::ok($artifact);
            },
        );
    }

    public function testHappyPatchForArtifact(): void
    {
        $section = new ArtidocSectionPOSTRepresentation(
            null,
            null,
            new POSTContentSectionRepresentation('title', 'description', 'artifact', []),
            Level::One->value,
        );

        $content_to_insert = ContentToBeCreatedBuilder::buildFromRepresentation($section);
        $content_to_insert->apply(
            function (int $artifact_id) {
                assertSame(null, $artifact_id);
                return Result::ok($artifact_id);
            },
            function (SectionContentToBeCreatedFreetext $freetext) {
                assertSame(null, $freetext);
                return Result::ok($freetext);
            },
            function (SectionContentToBeCreatedArtifact $artifact) {
                assertSame('title', $artifact->content->title);
                return Result::ok($artifact);
            },
        );
    }
}
