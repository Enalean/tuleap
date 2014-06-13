<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../include/autoload.php';
require_once dirname(__FILE__) .'/../../docman/include/Docman_PermissionsItemManager.class.php';
require_once dirname(__FILE__) .'/../../docman/include/Docman_MetadataFactory.class.php';
require_once dirname(__FILE__).'/Constants.php';
require_once dirname(__FILE__).'/builders/Parameters_Builder.php';

class FullTextSearchDocmanActionsTests extends TuleapTestCase {
    protected $client;
    protected $actions;
    protected $params;
    protected $permissions_manager;

    public function setUp() {
        parent::setUp();

        $this->client = partial_mock(
            'ElasticSearch_IndexClientFacade',
            array('index', 'update', 'delete')
        );

        $this->permissions_manager = mock('Docman_PermissionsItemManager');

        $metadata01 = stub('Docman_Metadata')->getId()->returns(1);
        $metadata02 = stub('Docman_Metadata')->getId()->returns(2);

        $this->item = aDocman_File()
            ->withId(101)
            ->withTitle('Coin')
            ->withDescription('Duck typing')
            ->withGroupId(200)
            ->build();

        $this->metadata_factory = stub('Docman_MetadataFactory')->getRealMetadataList()->returns(
            array($metadata01, $metadata02)
        );

        stub($this->metadata_factory)->getMetadataValue($this->item, $metadata01)->returns('val01');
        stub($this->metadata_factory)->getMetadataValue($this->item, $metadata02)->returns('val02');

        $this->actions = new FullTextSearchDocmanActions($this->client, $this->permissions_manager, $this->metadata_factory);

        stub($this->permissions_manager)
            ->exportPermissions($this->item)
            ->returns(array(3, 102));

        $this->version = stub('Docman_Version')
            ->getPath()
            ->returns(dirname(__FILE__) .'/_fixtures/file.txt');

//        stub($this->client)
//            ->initializeSetterData()
//            ->returns(array('script' => '', 'params' => array()));

        $this->params = aSetOfParameters()
            ->withItem($this->item)
            ->withVersion($this->version)
            ->build();
    }

    public function itCallIndexOnClientWithRightParameters() {
        $expected = array(
                          array(
                                'id'          => 101,
                                'group_id'    => 200,
                                'title'       => 'Coin',
                                'description' => 'Duck typing',
                                'permissions' => array(3, 102),
                                'file'        => 'aW5kZXggbWUK',
                                'property_1'  => 'val01',
                                'property_2'  => 'val02',
                               ),
                          101
                         );
        $this->client->expectOnce('index', $expected);

        $this->actions->indexNewDocument($this->item, $this->version);
    }

    public function itCallUpdateOnClientWithTitleIfNew() {
        $item_id     = $this->item->getId();
        $update_data = array(
            'script'=> 'ctx._source.title = title;'.
                       'ctx._source.description = description;'.
                       'ctx._source.property_1 = property_1;'.
                       'ctx._source.property_2 = property_2;',
            'params'=> array(
                'title'       => $this->item->getTitle(),
                'description' => $this->item->getDescription(),
                'property_1' => 'val01',
                'property_2' => 'val02',
            ),
        );

        $expected = array($item_id, $update_data);
        $this->client->expectOnce('update', $expected);

        $this->actions->updateDocument($this->item);
    }

    public function itCanDeleteADocumentFromItsId() {
        $expected_id = $this->item->getId();
        $this->client->expectOnce('delete', array($expected_id));

        $this->actions->delete($this->item);
    }
}