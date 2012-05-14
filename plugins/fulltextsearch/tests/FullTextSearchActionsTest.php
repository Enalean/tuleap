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

require_once dirname(__FILE__).'/Constants.php';
require_once dirname(__FILE__).'/clients/ElasticSearchFakeClient.php';
require_once dirname(__FILE__).'/builders/Parameters_Builder.php';

require_once dirname(__FILE__).'/../include/FullTextSearchActions.class.php';

Mock::generate('ElasticSearchFakeClient');


class FullTextSearchActionsTests extends TuleapTestCase {
    protected $client;
    protected $actions;
    protected $params;
    
    public function setUp() {
        parent::setUp();
        $this->client      = new MockElasticSearchFakeClient();
        $this->actions     = TestHelper::getPartialMock('FullTextSearchActions', array('file_content_encode'));
        $this->actions->__construct($this->client);
        $this->params      = aSetOfParameters();
    }
    
    public function itCallIndexOnClientWithRightParameters() {
        $this->params->item->withId(100)
            ->withGroupId(200)
            ->withPermissions(array('PLUGIN_DOCMAN_READ' =>  array(3, 102)))
        ;
        
        $expected_path = '/dir/file.php';
        $this->params->version->setReturnValue('getPath',     $expected_path);
        $this->actions->setReturnValue('file_content_encode', $expected_path);
        
        
        $this->client->expectOnce('index', $this->params->getClientIndexParameters());
        
        $this->actions->indexNewDocument($this->params->build());
    }
    
    
    public function itCanDeleteADocumentFromItsId() {
        $expected_id = 101;
        $this->params->item->withId($expected_id);
                
        $this->client->expectOnce('delete', array($expected_id));

        $this->actions->delete($this->params->build());
    }
    
    
    
    
}
?>