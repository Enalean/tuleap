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

declare(strict_types = 1);

namespace Tuleap\Docman\Metadata;

use Docman_Metadata;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataCollectionBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataRepresentationRetriever;
use Tuleap\Docman\REST\v1\Metadata\POSTCustomMetadataRepresentation;

class CustomMetadataRepresentationRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\MockInterface|MetadataListOfValuesElementListBuilder
     */
    private $list_values_builder;
    /**
     * @var \Mockery\MockInterface|CustomMetadataCollectionBuilder
     */
    private $collection_builder;
    /**
     * @var CustomMetadataRepresentationRetriever
     */
    private $checker;

    /**
     * @var \Docman_MetadataFactory|\Mockery\MockInterface
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory             = \Mockery::mock(\Docman_MetadataFactory::class);
        $this->list_values_builder = \Mockery::mock(MetadataListOfValuesElementListBuilder::class);

        $this->checker = new CustomMetadataRepresentationRetriever($this->factory, $this->list_values_builder);
    }

    public function testItThrownAnExceptionWhenMetadataShortNameIsNotFound(): void
    {
        $unknown_metadata             = new POSTCustomMetadataRepresentation();
        $unknown_metadata->short_name = "unknown_short_name";
        $unknown_metadata->value      = "text value";
        $unknown_metadata->list_value = null;

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "list value";
        $existing_metadata->list_value = null;

        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$unknown_metadata->short_name])->andReturn(null);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn("field_list_1");

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('metadata unknown_short_name is not found');

        $this->checker->checkAndRetrieveFormattedRepresentation([$unknown_metadata, $existing_metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsAListWithMultipleValuesAndWhenValueIsProvided(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("metadata field_list_1 is a multiple list");

        $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsASimpleListAndWhenListValueIsProvided(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [100];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnFalse();
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("list field_list_1 has too many values");

        $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsNotAListAndWhenListValueIsProvided(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = null;
        $metadata->list_value = [101];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("metadata field_text_1 is not a list and a list_value is provided");

        $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsAListWithoutMultipleValuesAndMoreThanOneValueIsProvided(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [101, 102];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("list field_list_1 has too many values");

        $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
    }

    public function testItThrowsAnExceptionWhenListValueIdDoesNotExist(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [999];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("value: 999 are unknown for metadata field_list_1");

        $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
    }


    public function testMetadataIsValidWhenListValueAreEmptyAndMetadataIsNotRequired(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $expected_representation = [
            $metadata->short_name => $metadata->list_value
        ];
        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testMetadataIsValidWhenTextValueIsEmptyAndMetadataIsNotRequired(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = null;
        $metadata->list_value = null;

        $expected_representation = [
            $metadata->short_name => $metadata->value
        ];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }


    public function testMetadataIsValidWhenListValueAreProvided(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [1];

        $expected_representation = [
            $metadata->short_name => $metadata->list_value
        ];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testMetadataIsValidWhenTextValueIsProvided(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;

        $expected_representation = [
            $metadata->short_name => $metadata->value
        ];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation([$metadata]);
        $this->assertEquals($expected_representation, $formatted_representation);
    }

    public function testMetadataIsValidWhenProjectMetadataIsNotUsed(): void
    {
        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation(null);
        $this->assertEquals($formatted_representation, []);
    }

    public function testItThrownAnExceptionForFileWhenMetadataShortNameIsNotFound(): void
    {
        $unknown_metadata             = new POSTCustomMetadataRepresentation();
        $unknown_metadata->short_name = "unknown_short_name";
        $unknown_metadata->value      = "text value";
        $unknown_metadata->list_value = null;

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "list value";
        $existing_metadata->list_value = null;

        $this->factory->shouldReceive('getFromLabel')->withArgs([$unknown_metadata->short_name])->andReturn(null);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$existing_metadata->short_name])->andReturn("field_list_1");

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('metadata unknown_short_name is not found');

        $this->checker->checkAndRetrieveFileFormattedRepresentation([$unknown_metadata, $existing_metadata]);
    }

    public function testItBuildTextRepresentationForFile(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;

        $expected_representation[] = [
            'id'    => 1,
            'value' => $metadata->value
        ];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $formatted_representation = $this->checker->checkAndRetrieveFileFormattedRepresentation([$metadata]);
        $this->assertEquals($expected_representation, $formatted_representation);
    }

    public function testItBuildSimpleRepresentationForFile(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = 1;
        $metadata->list_value = null;

        $expected_representation[] = [
            'id'    => 1,
            'value' => $metadata->value
        ];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnFalse();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $formatted_representation = $this->checker->checkAndRetrieveFileFormattedRepresentation([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testItBuildListWithMultipleValuesRepresentationForFile(): void
    {
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [101, 102];

        $expected_representation[] = [
            'id'         => 1,
            'value'      => $metadata->list_value,
        ];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);

        $value     = ['value_id' => 101, 'name' => 'value'];
        $value_two = ['value_id' => 102, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $formatted_representation = $this->checker->checkAndRetrieveFileFormattedRepresentation([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }
}
