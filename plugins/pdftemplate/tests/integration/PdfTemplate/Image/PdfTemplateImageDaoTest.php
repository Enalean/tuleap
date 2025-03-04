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

namespace Tuleap\PdfTemplate\Image;

use DateTimeImmutable;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PdfTemplateImageDaoTest extends TestIntegrationTestCase
{
    public function testImagesAreRetrievedOrderedByFilname(): void
    {
        $alice = UserTestBuilder::aUser()->build();

        $identifier_factory = new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateImageDao($identifier_factory, RetrieveUserByIdStub::withUser($alice));

        self::assertCount(0, $dao->retrieveAll());

        $dao->create($identifier_factory->buildIdentifier(), 'the logo.png', 123, $alice, new DateTimeImmutable());
        $dao->create($identifier_factory->buildIdentifier(), 'another logo.gif', 456, $alice, new DateTimeImmutable());

        $templates = $dao->retrieveAll();

        self::assertCount(2, $templates);
        self::assertEquals('another logo.gif', $templates[0]->filename);
        self::assertEquals('the logo.png', $templates[1]->filename);
    }

    public function testImageDeletion(): void
    {
        $alice = UserTestBuilder::aUser()->build();

        $identifier_factory = new PdfTemplateImageIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateImageDao($identifier_factory, RetrieveUserByIdStub::withUser($alice));

        $identifier1 = $identifier_factory->buildIdentifier();
        $image1      = $dao->create($identifier1, 'the logo.png', 123, $alice, new DateTimeImmutable());

        $identifier2 = $identifier_factory->buildIdentifier();
        $image2      = $dao->create($identifier2, 'another logo.gif', 456, $alice, new DateTimeImmutable());

        $this->assertSameImages($image1, $dao->retrieveImage($identifier1));
        $this->assertSameImages($image2, $dao->retrieveImage($identifier2));

        $dao->deleteImage($image1);

        self::assertNull($dao->retrieveImage($identifier1));
        $this->assertSameImages($image2, $dao->retrieveImage($identifier2));
    }

    private function assertSameImages(PdfTemplateImage $expected, ?PdfTemplateImage $image): void
    {
        self::assertNotNull($image);
        self::assertSame($expected->identifier->toString(), $image->identifier->toString());
    }
}
