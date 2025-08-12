<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use ArrayIterator;
use Codendi_HTMLPurifier;
use Docman_Item;
use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElement;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataListValueRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataRepresentationBuilderTest extends TestCase
{
    public function testItBuildMetadataWithoutBasicProperties(): void
    {
        $item = new Docman_Item();

        $factory       = $this->createMock(Docman_MetadataFactory::class);
        $html_purifier = $this->createMock(Codendi_HTMLPurifier::class);
        $builder       = new MetadataRepresentationBuilder($factory, $html_purifier, $this->createMock(UserHelper::class));

        $simple_metadata = new Docman_Metadata();
        $simple_metadata->setValue('my simple value');
        $simple_metadata->setIsMultipleValuesAllowed(false);
        $simple_metadata->setName('simple metadata label');
        $simple_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $simple_metadata->setisEmptyAllowed(true);
        $simple_metadata->setLabel('simple_metadata_label');
        $simple_metadata->setGroupId(102);
        $simple_metadata->setSpecial(false);

        $value1 = new Docman_MetadataListOfValuesElement();
        $value1->setId(1);
        $value1->setName('My value 1');
        $value2 = new Docman_MetadataListOfValuesElement();
        $value2->setId(100);
        $list_metadata = new Docman_ListMetadata();
        $list_metadata->setValue(new ArrayIterator([$value1, $value2]));
        $list_metadata->setIsMultipleValuesAllowed(true);
        $list_metadata->setName('list metadata label');
        $list_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $list_metadata->setIsEmptyAllowed(false);
        $list_metadata->setLabel('list_metadata_label');
        $list_metadata->setSpecial(false);

        $factory->method('appendItemMetadataList');
        $item->addMetadata($simple_metadata);
        $item->addMetadata($list_metadata);
        $html_purifier->method('purifyTextWithReferences')->willReturn('value with references');

        $representation = $builder->build($item);

        $expected_representation = [
            new ItemMetadataRepresentation(
                'list metadata label',
                'list',
                true,
                null,
                null,
                [
                    new MetadataListValueRepresentation(1, 'My value 1'),
                    new MetadataListValueRepresentation(100, 'None'),
                ],
                false,
                'list_metadata_label'
            ),
            new ItemMetadataRepresentation(
                'simple metadata label',
                'text',
                false,
                'my simple value',
                'value with references',
                null,
                true,
                'simple_metadata_label'
            ),
        ];

        self::assertEquals($expected_representation, $representation);
    }

    public function testMetadataWithDatePropertyIsCorrectlyBuilt(): void
    {
        $item = new Docman_Item();

        $factory = $this->createMock(Docman_MetadataFactory::class);
        $builder = new MetadataRepresentationBuilder(
            $factory,
            $this->createMock(Codendi_HTMLPurifier::class),
            $this->createMock(UserHelper::class)
        );

        $date_metadata = new Docman_Metadata();
        $date_metadata->setValue('1');
        $date_metadata->setIsMultipleValuesAllowed(false);
        $date_metadata->setName('date metadata name');
        $date_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $date_metadata->setIsEmptyAllowed(true);
        $date_metadata->setLabel('date metadata label');
        $date_metadata->setSpecial(false);

        $factory->method('appendItemMetadataList');
        $item->addMetadata($date_metadata);

        $representation          = $builder->build($item);
        $expected_representation = new ItemMetadataRepresentation(
            'date metadata name',
            'date',
            false,
            '1970-01-01T01:00:01+01:00',
            '1970-01-01T01:00:01+01:00',
            null,
            true,
            'date metadata label'
        );

        self::assertEquals([$expected_representation], $representation);
    }

    #[\PHPUnit\Framework\Attributes\TestWith([0])]
    #[\PHPUnit\Framework\Attributes\TestWith(['0'])]
    public function testMetadataWithDatePropertyButWithoutActualValueIsCorrectlyBuilt($value): void
    {
        $item = new Docman_Item();

        $factory = $this->createMock(Docman_MetadataFactory::class);
        $builder = new MetadataRepresentationBuilder(
            $factory,
            $this->createMock(Codendi_HTMLPurifier::class),
            $this->createMock(UserHelper::class)
        );

        $date_metadata = new Docman_Metadata();
        $date_metadata->setValue($value);
        $date_metadata->setIsMultipleValuesAllowed(false);
        $date_metadata->setName('date metadata name');
        $date_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $date_metadata->setIsEmptyAllowed(true);
        $date_metadata->setLabel('date metadata label');
        $date_metadata->setSpecial(false);

        $factory->method('appendItemMetadataList');
        $item->addMetadata($date_metadata);

        $representation          = $builder->build($item);
        $expected_representation = new ItemMetadataRepresentation(
            'date metadata name',
            'date',
            false,
            null,
            null,
            null,
            true,
            'date metadata label'
        );

        self::assertEquals([$expected_representation], $representation);
    }

    public function testMetadataOwnerPropertyIsCorrectlyBuilt(): void
    {
        $item = new Docman_Item();

        $factory     = $this->createMock(Docman_MetadataFactory::class);
        $user_helper = $this->createMock(UserHelper::class);
        $builder     = new MetadataRepresentationBuilder(
            $factory,
            $this->createMock(Codendi_HTMLPurifier::class),
            $user_helper
        );

        $owner_metadata = new Docman_Metadata();
        $owner_metadata->setValue('1');
        $owner_metadata->setIsMultipleValuesAllowed(false);
        $owner_metadata->setName('owner');
        $owner_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $owner_metadata->setIsEmptyAllowed(true);
        $owner_metadata->setLabel('owner');
        $owner_metadata->setSpecial(false);

        $factory->method('appendItemMetadataList');
        $item->addMetadata($owner_metadata);

        $user_helper->method('getDisplayNameFromUserId')->willReturn('user display name');
        $user_helper->method('getLinkOnUserFromUserId')->willReturn('user display name with link');

        $representation          = $builder->build($item);
        $expected_representation = new ItemMetadataRepresentation(
            'owner',
            'string',
            false,
            'user display name',
            'user display name with link',
            null,
            true,
            'owner'
        );

        self::assertEquals([$expected_representation], $representation);
    }
}
