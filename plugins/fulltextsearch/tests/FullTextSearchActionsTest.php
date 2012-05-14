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
require_once dirname(__FILE__).'/builders/Docman_File_Builder.php';

require_once DOCMAN_INCLUDE_PATH.'/Docman_Version.class.php';

require_once dirname(__FILE__).'/../include/FullTextSearchActions.class.php';

Mock::generate('ElasticSearchFakeClient');
//Mock::generate('FullTextSearchActions');
Mock::generate('Docman_Version');


class FullTextSearchActionsTests extends TuleapTestCase {
    protected $client;
    protected $actions;
    protected $params;
    protected $docman_file;
    
    public function setUp() {
        parent::setUp();
        $this->docman_file = aDocman_File();
        $this->client      = new MockElasticSearchFakeClient();
        $this->actions     = TestHelper::getPartialMock('FullTextSearchActions', array('file_content_encode'));
        $this->actions->__construct($this->client);
        $this->params      = array(
        	'item' => '',
            'version' => new MockDocman_Version(),
            'user' => '',
        ); 
    }
    
    public function itCallIndexOnClientWithRightParameters() {
        $expected_id          = 100;
        $expected_group_id    = 200;
        $expected_title       = 'title'; 
        $expected_description = 'description';
        $expected_path        = 'path';
        $permissions          = array('PLUGIN_DOCMAN_READ' =>  array(3, 102));
        
        $this->docman_file
            ->withId($expected_id)
            ->withGroupId($expected_group_id)
            ->withTitle($expected_title)
            ->withDescription($expected_description)
            ->withPermissions($permissions)
        ;
        
        $this->params['item'] = $this->docman_file->build();
        $this->params['version']->setReturnValue('getPath',     $expected_path);
        $this->actions->setReturnValue('file_content_encode', $expected_path);
        
        $expected_datas       = array(
                'title'       => $expected_title,
                'description' => $expected_description,
                'file'        => $expected_path,
                'permissions' => array($expected_group_id => array(3, 102))
        );
        $this->client->expectOnce('index', array($expected_datas, $expected_id));
        
        $this->actions->indexNewDocument($this->params);
    }
    
    
    public function itCanDeleteADocumentFromItsId() {
        $expected_id          = 101;
        $this->docman_file->withId($expected_id);
        $this->params['item'] = $this->docman_file->build();
                
        $this->client->expectOnce('delete', array($expected_id));

        $this->actions->delete($this->params);
    }
    
    
    
    
}
?>