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
require_once dirname(__FILE__).'/Constants.php';
require_once dirname(__FILE__).'/builders/Parameters_Builder.php';

class FullTextSearchDocmanActionsTests extends TuleapTestCase {
    protected $client;
    protected $actions;
    protected $params;
    protected $permissions_manager;

    public function setUp() {
        parent::setUp();

        $this->client              = mock('FullTextSearch_IIndexDocuments');
        $this->permissions_manager = mock('Docman_PermissionsItemManager');
        $this->actions = new FullTextSearchDocmanActions($this->client, $this->permissions_manager);

        $this->item = aDocman_File()
            ->withId(101)
            ->withTitle('Coin')
            ->withDescription('Duck typing')
            ->withGroupId(200)
            ->build();

        stub($this->permissions_manager)
            ->exportPermissions($this->item)
            ->returns(array(3, 102));

        $this->version = stub('Docman_Version')
            ->getPath()
            ->returns(dirname(__FILE__) .'/_fixtures/file.txt');

        stub($this->client)
            ->initializeSetterData()
            ->returns(array('script' => '', 'params' => array()));

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
                               ),
                          101
                         );
        $this->client->expectOnce('index', $expected);

        $this->actions->indexNewDocument($this->item, $this->version);
    }

    public function itCallUpdateOnClientWithTitleIfNew() {
        $item_id = $this->item->getId();
        $update_data = array(
            'script'=> 'ctx._source.title = title; ctx._source.description = description',
            'params'=> array(
                'title'       => $this->item->getTitle(),
                'description' => $this->item->getDescription()
            ),
        );
        stub($this->client)->appendSetterData()->returns($update_data);

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
?>
