<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use DateTime;
use Docman_Metadata;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanMetadataInputValidatorTest extends TestCase
{
    private DocmanMetadataInputValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DocmanMetadataInputValidator();
    }

    public function testItDoesNothingForTextMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $value = 'text';

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals($value, $validated_input);
    }

    public function testItDoesNothingForStringMetadata(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $value = 'string';

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals($value, $validated_input);
    }

    public function testItExtractTimestampFromDate(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $value = '2019-08-02';

        $validated_input = $this->validator->validateInput($metadata, $value);

        $expected_date = DateTime::createFromFormat('Y-m-d H:i:s', '2019-08-02 00:00:00');
        self::assertEquals($expected_date->getTimestamp(), $validated_input);
    }

    public function testItTransformDateToZeroIfDateIsNotParsable(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $value = 'aaa';

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals(0, $validated_input);
    }

    public function testItReturnsATimestamp(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $value = '1564750064';

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals($value, $validated_input);
    }

    public function testItDoesNothingForMultipleValues(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->setIsMultipleValuesAllowed(true);

        $value = [100, 101];

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals($value, $validated_input);
    }

    public function testItReturnsFirstValueForSingleListWithMultipleValues(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->setIsMultipleValuesAllowed(false);

        $value = [101, 102];

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals(101, $validated_input);
    }

    public function testItDoesNothingSimpleListWithOnlyOneValue(): void
    {
        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->setIsMultipleValuesAllowed(false);
        $value = 101;

        $validated_input = $this->validator->validateInput($metadata, $value);

        self::assertEquals(101, $validated_input);
    }
}
