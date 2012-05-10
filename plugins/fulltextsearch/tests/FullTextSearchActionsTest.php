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

require_once dirname(__FILE__).'/clients/ElasticSearchFakeClient.php';
if (!defined('DOCMAN_PATH')) {
    define('DOCMAN_INCLUDE_PATH', dirname(__FILE__).'/../../docman/include' );
}
require_once DOCMAN_INCLUDE_PATH.'/Docman_File.class.php';
require_once DOCMAN_INCLUDE_PATH.'/Docman_Version.class.php';

require_once dirname(__FILE__).'/../include/FullTextSearchActions.class.php';

Mock::generate('User');
Mock::generate('ElasticSearchFakeClient');
Mock::generate('FullTextSearchActions');
Mock::generate('Docman_File');
Mock::generate('Docman_Version');


class FullTextSearchActionsTests extends TuleapTestCase {
    protected $client;
    public function setUp() {
        parent::setUp();
        $this->client  = new MockElasticSearchFakeClient();
        $this->actions = TestHelper::getPartialMock('FullTextSearchActions', array('file_content_encode'));
        $this->actions->__construct($this->client);
        $this->params  = array(
        	'item' => new MockDocman_File(),
            'version' => new MockDocman_Version(),
            'user' => new MockUser(),
        ); 
    }
    
    public function itCallIndexOnClientWithRightParameters() {
        $expected_id          = 'id';
        $expected_title       = 'title'; 
        $expected_description = 'description';
        $expected_path        = 'path';
        $expected_datas       = array(
            'title'       => $expected_title,
            'description' => $expected_description,
            'file'        => $expected_path
        );
        
        $this->params['item']->setReturnValue('getId',          $expected_id);
        $this->params['item']->setReturnValue('getTitle',       $expected_title);
        $this->params['item']->setReturnValue('getDescription', $expected_description);
        $this->params['version']->setReturnValue('getPath',        $expected_path);
        
        $this->actions->setReturnValue('file_content_encode', $expected_path);
        $this->client->expectOnce('index', array($expected_datas, $expected_id));
        
        $this->actions->indexNewDocument($this->params);
    }
    
    
    public function itCanDeleteADocumentFromItsId() {
        $expected_id          = 'id';                
        $this->params['item']->setReturnValue('getId', $expected_id);
                
        $this->client->expectOnce('delete', array($expected_id));

        $this->actions->delete($this->params);
    }
    
    
    
    
}
?>