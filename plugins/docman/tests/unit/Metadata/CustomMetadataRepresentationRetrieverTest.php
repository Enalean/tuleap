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

use Docman_Metadata;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataCollection;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataCollectionBuilder;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataRepresentationRetriever;
use Tuleap\Docman\REST\v1\Metadata\ProjectConfiguredMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataToCreate;
use Tuleap\Docman\REST\v1\Metadata\MetadataToUpdate;
use Tuleap\Docman\REST\v1\Metadata\POSTCustomMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\PUTCustomMetadataRepresentation;

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
        $this->collection_builder  = \Mockery::mock(CustomMetadataCollectionBuilder::class);

        $this->checker = new CustomMetadataRepresentationRetriever(
            $this->factory,
            $this->list_values_builder,
            $this->collection_builder
        );
    }

    public function testItThrowsAnExceptionWhenMetadataKeyIsProvidedWithoutAllMetadataInside(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "list value";
        $existing_metadata->list_value = null;

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $project_list_representation = new ProjectConfiguredMetadataRepresentation();
        $project_list_representation->build(
            "field_list_1",
            'field_list_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn(
            "field_list_1"
        );
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation, $project_list_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('missing metadata keys: field_list_1');

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$existing_metadata]);
    }

    public function testItThrowsAnExceptionWhenTextRequiredMetadataIsEmptyInRepresentation(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "";
        $existing_metadata->list_value = null;

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            false,
            false,
            true,
            null
        );

        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn(
            "field_list_1"
        );
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('missing required values for: field_text_1');

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$existing_metadata]);
    }

    public function testItThrowsAnExceptionWhenListRequiredMetadataIsEmptyInRepresentation(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_list_1";
        $existing_metadata->value      = null;
        $existing_metadata->list_value = "";

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'field_list_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            false,
            false,
            true,
            null
        );

        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn(
            "field_list_1"
        );
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('missing required values for: field_list_1');

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$existing_metadata]);
    }

    public function testItThrowsAnExceptionRequiredMetadataIsNullInRepresentation(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = null;
        $existing_metadata->list_value = null;

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            false,
            false,
            true,
            null
        );

        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn(
            "field_list_1"
        );
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('missing required values for: field_text_1');

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$existing_metadata]);
    }

    public function testItDoesNotThrowAnExceptionWhenProjectMetadataAreUnUsed(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "list value";
        $existing_metadata->list_value = null;

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            false,
            false,
            false,
            null
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($existing_metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $expected_metadata_to_create = MetadataToCreate::buildMetadataRepresentation(
            ['field_text_1' => 'list value'],
            false
        );
        $metadata_to_create          = $this->checker->checkAndRetrieveFormattedRepresentation(
            $item,
            [$existing_metadata]
        );

        $this->assertEquals($expected_metadata_to_create, $metadata_to_create);
    }

    public function testMetadataIsInheritedFromParentWhenMetadataKeyIsNotProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            false,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $expected_metadata_to_create = MetadataToCreate::buildMetadataRepresentation([], true);
        $metadata_to_create          = $this->checker->checkAndRetrieveFormattedRepresentation($item, null);

        $this->assertEquals($expected_metadata_to_create, $metadata_to_create);
    }

    public function testMetadataIsNotValidFromParentWhenMetadataKeyIsAnEmptyArray(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            false,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('missing metadata keys: field_text_1');

        $this->checker->checkAndRetrieveFormattedRepresentation($item, []);
    }

    public function testMetadataIsInheritedForFileFromParentWhenMetadataKeyIsNotProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'field_text_1',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            false,
            false,
            false,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $expected_metadata_to_create = MetadataToCreate::buildMetadataRepresentation([], true);
        $metadata_to_create          = $this->checker->checkAndRetrieveFileFormattedRepresentation($item, null);

        $this->assertEquals($expected_metadata_to_create, $metadata_to_create);
    }

    public function testItThrownAnExceptionWhenMetadataShortNameIsNotFound(): void
    {
        $item                         = \Mockery::mock(\Docman_Item::class);
        $unknown_metadata             = new POSTCustomMetadataRepresentation();
        $unknown_metadata->short_name = "unknown_short_name";
        $unknown_metadata->value      = "text value";
        $unknown_metadata->list_value = null;

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "list value";
        $existing_metadata->list_value = null;

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$unknown_metadata->short_name])->andReturn(null);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$existing_metadata->short_name])->andReturn("field_list_1");
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('metadata unknown_short_name is not found');

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$unknown_metadata, $existing_metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsAListWithMultipleValuesAndWhenValueIsProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("metadata field_list_1 is a multiple list");

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsASimpleListAndWhenListValueIsProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [100];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnFalse();
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn($project_configured_metadata);
        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("list field_list_1 has too many values");

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsNotAListAndWhenListValueIsProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = null;
        $metadata->list_value = [101];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("metadata field_text_1 is not a list and a list_value is provided");

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
    }

    public function testItThrowsAnExceptionWhenMetadataIsAListWithoutMultipleValuesAndMoreThanOneValueIsProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [101, 102];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("list field_list_1 has too many values");

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
    }

    public function testItThrowsAnExceptionWhenListValueIdDoesNotExist(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [999];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage("value: 999 are unknown for metadata field_list_1");

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
    }

    public function testMetadataIsValidWhenListValueAreEmptyAndMetadataIsNotRequired(): void
    {
        $item                 = \Mockery::mock(\Docman_Item::class);
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [];

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $expected_representation  = MetadataToCreate::buildMetadataRepresentation(
            [$metadata->short_name => $metadata->list_value],
            false
        );
        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testMetadataIsValidWhenTextValueIsEmptyAndMetadataIsNotRequired(): void
    {
        $item                 = \Mockery::mock(\Docman_Item::class);
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = null;
        $metadata->list_value = null;

        $expected_representation = MetadataToCreate::buildMetadataRepresentation(
            [$metadata->short_name => $metadata->value],
            false
        );

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testMetadataIsValidWhenListValueAreProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [1];

        $expected_representation = MetadataToCreate::buildMetadataRepresentation(
            [$metadata->short_name => $metadata->list_value],
            false
        );

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testMetadataIsValidWhenTextValueIsProvided(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;

        $expected_representation     = MetadataToCreate::buildMetadataRepresentation(
            [$metadata->short_name => $metadata->value],
            false
        );
        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($expected_representation, $formatted_representation);
    }

    public function testMetadataIsValidWhenProjectMetadataIsNotUsed(): void
    {
        $item                        = \Mockery::mock(\Docman_Item::class);
        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $this->collection_builder->shouldReceive('build')->andReturn(CustomMetadataCollection::build([]));

        $expected_representation = MetadataToCreate::buildMetadataRepresentation(
            [],
            true
        );

        $formatted_representation = $this->checker->checkAndRetrieveFormattedRepresentation($item, null);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testItThrownAnExceptionForFileWhenMetadataShortNameIsNotFound(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $unknown_metadata             = new POSTCustomMetadataRepresentation();
        $unknown_metadata->short_name = "unknown_short_name";
        $unknown_metadata->value      = "text value";
        $unknown_metadata->list_value = null;

        $existing_metadata             = new POSTCustomMetadataRepresentation();
        $existing_metadata->short_name = "field_text_1";
        $existing_metadata->value      = "list value";
        $existing_metadata->list_value = null;

        $this->factory->shouldReceive('getFromLabel')->withArgs([$unknown_metadata->short_name])->andReturn(null);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$existing_metadata->short_name])->andReturn(
            "field_list_1"
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $this->expectException(CustomMetadataException::class);
        $this->expectExceptionMessage('metadata unknown_short_name is not found');

        $this->checker->checkAndRetrieveFileFormattedRepresentation($item, [$unknown_metadata, $existing_metadata]);
    }

    public function testItBuildTextRepresentationForFile(): void
    {
        $item                 = \Mockery::mock(\Docman_Item::class);
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;

        $expected_representation = MetadataToCreate::buildMetadataRepresentation(
            [
                [
                    'id'    => 1,
                    'value' => $metadata->value
                ]
            ],
            false
        );

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_text_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $formatted_representation = $this->checker->checkAndRetrieveFileFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($expected_representation, $formatted_representation);
    }

    public function testItBuildSimpleRepresentationForFile(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = 1;
        $metadata->list_value = null;

        $expected_representation = MetadataToCreate::buildMetadataRepresentation(
            [
                [
                    'id'    => 1,
                    'value' => $metadata->value
                ]
            ],
            false
        );

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnFalse();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            false,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $formatted_representation = $this->checker->checkAndRetrieveFileFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testItBuildListWithMultipleValuesRepresentationForFile(): void
    {
        $item                 = \Mockery::mock(\Docman_Item::class);
        $metadata             = new POSTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [101, 102];

        $expected_representation = MetadataToCreate::buildMetadataRepresentation(
            [
                [
                    'id'    => 1,
                    'value' => $metadata->list_value
                ]
            ],
            false
        );

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $value     = ['value_id' => 101, 'name' => 'value'];
        $value_two = ['value_id' => 102, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $this->factory->shouldReceive('appendItemMetadataList')->once();

        $project_field_representation = new ProjectConfiguredMetadataRepresentation();
        $project_field_representation->build(
            "field_list_1",
            'name',
            'description',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            true,
            true,
            true,
            null
        );

        $this->collection_builder->shouldReceive('build')->andReturn(
            CustomMetadataCollection::build([$project_field_representation])
        );

        $formatted_representation = $this->checker->checkAndRetrieveFileFormattedRepresentation($item, [$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testItBuildMetadataToUpdateForText(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new PUTCustomMetadataRepresentation();
        $metadata->short_name = "field_text_1";
        $metadata->value      = "my value";
        $metadata->list_value = null;
        $metadata->recursion  = "none";

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $expected_representation[] = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $metadata->value,
            $metadata->recursion
        );

        $formatted_representation = $this->checker->checkAndBuildFolderMetadataToUpdate([$metadata]);
        $this->assertEquals($expected_representation, $formatted_representation);
    }

    public function testItBuildMetadataToUpdateForListWithSingleValue(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new PUTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = 1;
        $metadata->list_value = null;
        $metadata->recursion  = "none";

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnFalse();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $expected_representation[] = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $metadata->value,
            $metadata->recursion
        );

        $value     = ['value_id' => 1, 'name' => 'value'];
        $value_two = ['value_id' => 2, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $formatted_representation = $this->checker->checkAndBuildFolderMetadataToUpdate([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }

    public function testItBuildMetadataToUpdateForListWithMultipleValues(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);

        $metadata             = new PUTCustomMetadataRepresentation();
        $metadata->short_name = "field_list_1";
        $metadata->value      = null;
        $metadata->list_value = [101, 102];
        $metadata->recursion  = "none";

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $project_configured_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $project_configured_metadata->shouldReceive('isMultipleValuesAllowed')->andReturnTrue();
        $project_configured_metadata->shouldReceive('getLabel')->andReturn($metadata->short_name);
        $project_configured_metadata->shouldReceive('getId')->andReturn(1);
        $this->factory->shouldReceive('getMetadataFromLabel')->withArgs([$metadata->short_name])->andReturn(
            $project_configured_metadata
        );

        $expected_representation[] = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $metadata->list_value,
            $metadata->recursion
        );

        $value     = ['value_id' => 101, 'name' => 'value'];
        $value_two = ['value_id' => 102, 'name' => 'name value 2'];

        $element = new \Docman_MetadataListOfValuesElement();
        $element->initFromRow($value);

        $element_two = new \Docman_MetadataListOfValuesElement();
        $element_two->initFromRow($value_two);

        $this->list_values_builder->shouldReceive('build')->withArgs([1, true])->andReturn([$element, $element_two]);

        $formatted_representation = $this->checker->checkAndBuildFolderMetadataToUpdate([$metadata]);
        $this->assertEquals($formatted_representation, $expected_representation);
    }
}
