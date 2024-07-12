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

namespace Tuleap\PdfTemplate\Admin;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\Test\PHPUnit\TestCase;

final class UpdateTemplateRequestTest extends TestCase
{
    public function testChangedFields(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());
        $identifier         = $identifier_factory->buildIdentifier();

        self::assertEquals(
            [],
            (new UpdateTemplateRequest(
                new PdfTemplate($identifier, 'label', 'description', 'style'),
                new PdfTemplate($identifier, 'label', 'description', 'style'),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['label'],
            (new UpdateTemplateRequest(
                new PdfTemplate($identifier, 'label', 'description', 'style'),
                new PdfTemplate($identifier, 'update label', 'description', 'style'),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['description'],
            (new UpdateTemplateRequest(
                new PdfTemplate($identifier, 'label', 'description', 'style'),
                new PdfTemplate($identifier, 'label', 'update description', 'style'),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['style'],
            (new UpdateTemplateRequest(
                new PdfTemplate($identifier, 'label', 'description', 'style'),
                new PdfTemplate($identifier, 'label', 'description', 'updated style'),
            ))->getChangedFields(),
        );

        self::assertEquals(
            ['label', 'style'],
            (new UpdateTemplateRequest(
                new PdfTemplate($identifier, 'label', 'description', 'style'),
                new PdfTemplate($identifier, 'updated label', 'description', 'updated style'),
            ))->getChangedFields(),
        );
    }
}
