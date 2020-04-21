<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use PHPUnit\Framework\TestCase;

class IdForXMLImportExportConvertorTest extends TestCase
{
    public function testItConvertsForXMLExport(): void
    {
        $this->assertEquals('fileinfo_123', IdForXMLImportExportConvertor::convertFileInfoIdToXMLId(123));
    }

    public function testItConvertsForXMLImport(): void
    {
        $this->assertEquals(123, IdForXMLImportExportConvertor::convertXMLIdToFileInfoId('fileinfo_123'));
    }


    /**
     * @testWith [""]
     *           ["invalid_prefix_123"]
     *           ["fileinfo_"]
     *           ["fileinfo_string"]
     */
    public function testInvalidXMLIds(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);
        IdForXMLImportExportConvertor::convertXMLIdToFileInfoId($id);
    }
}
