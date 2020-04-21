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
use Mockery;
use PHPUnit\Framework\TestCase;

class DocmanMetadataInputValidatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var DocmanMetadataInputValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new DocmanMetadataInputValidator();
    }

    public function testItDoesNothingForTextMetadata(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $value = 'text';

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, $value);
    }

    public function testItDoesNothingForStringMetadata(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $value = 'string';

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, $value);
    }

    public function testItExtractTimestampFromDate(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $value = '2019-08-02';

        $validated_input = $this->validator->validateInput($metadata, $value);

        $expected_date = DateTime::createFromFormat("Y-m-d H:i:s", '2019-08-02 00:00:00');
        $this->assertEquals($validated_input, $expected_date->getTimestamp());
    }

    public function testItTransformDateToZeroIfDateIsNotParsable(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $value = 'aaa';

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, 0);
    }

    public function testItReturnsATimestamp(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $value = '1564750064';

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, $value);
    }

    public function testItDoesNothingForMultipleValues(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(true);

        $value = [100, 101];

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, $value);
    }

    public function testItReturnsFirstValueForSingleListWithMultipleValues(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);

        $value = [101, 102];

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, 101);
    }

    public function testItDoesNothingSimpleListWithOnlyOneValue(): void
    {
        $metadata = Mockery::mock(Docman_Metadata::class);
        $metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $value = 101;

        $validated_input = $this->validator->validateInput($metadata, $value);

        $this->assertEquals($validated_input, 101);
    }
}
