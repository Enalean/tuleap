<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * 
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/*require_once(dirname(__FILE__).'/../include/Docman_VersionFactory.class.php');
Mock::generatePartial('Docman_VersionFactory','Docman_VersionFactoryTest', array('_getVersionDao',));

require_once(dirname(__FILE__).'/../include/Docman_VersionDao.class.php');
Mock::generate('Docman_VersionDao');

require_once('common/project/Project.class.php');
Mock::generate('Project');*/

require_once(dirname(__FILE__).'/../include/Docman_Actions.class.php');
Mock::generatePartial('Docman_Actions','Docman_ActionsTest', array('_getItemFactory',
                                                                   '_getFileStorage',
                                                                   '_getActionsDeleteVisitor',
                                                                   '_getEventManager'));

require_once(dirname(__FILE__).'/../include/Docman_Controller.class.php');
Mock::generate('Docman_Controller');

require_once('common/valid/ValidFactory.class.php');

Mock::generate('HTTPRequest');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_Folder');
Mock::generate('Docman_File');
Mock::generate('Feedback');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

Mock::generate('EventManager');

class DocmanActionsTest extends UnitTestCase {

    function __construct($name = 'DocmanActions test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    
    function tearDown() {
        unset($GLOBALS['Language']);
    }

    function testCannotDeleteVersionOnNonFile() {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = new MockDocman_Controller($this);
        $ctrl->feedback = new MockFeedback($this);
        // Test log message
        $ctrl->feedback->expectOnce('log', array('error', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'bla');
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'error_item_not_deleted_nonfile_version'));

        // Setup of the test
        $ctrl->request = new MockHTTPRequest($this);
        $ctrl->request->setReturnValue('get', '102', array('group_id'));
        $ctrl->request->setReturnValue('get', '344', array('id'));
        $ctrl->request->setReturnValue('get', '1', array('version'));
        $ctrl->request->setReturnValue('valid', true);

        $item = new MockDocman_Folder($this);
        
        $if = new MockDocman_ItemFactory($this);
        $if->setReturnValue('getItemFromDb', $item);
        $if->expectOnce('getItemFromDb', array(344));
        $if->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        
        $actions = new Docman_ActionsTest($this);
        $actions->_controler = $ctrl;
        $actions->setReturnValue('_getItemFactory', $if);
        $actions->expectOnce('_getItemFactory', array(102));
        $actions->setReturnValue('_getEventManager', new MockEventManager($this));

        // Run test
        $actions->deleteVersion();
    }
    
    function testCanDeleteVersionOfFile() {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = new MockDocman_Controller($this);
        $ctrl->feedback = new MockFeedback($this);
        // Test log message
        $ctrl->feedback->expectOnce('log', array('info', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'bla');
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'info_item_deleted'));

        // Setup of the test
        $ctrl->request = new MockHTTPRequest($this);
        $ctrl->request->setReturnValue('get', '102', array('group_id'));
        $ctrl->request->setReturnValue('get', '344', array('id'));
        $ctrl->request->setReturnValue('get', '1', array('version'));
        $ctrl->request->setReturnValue('valid', true);

        $item = new MockDocman_File($this);
        $item->setReturnValue('getParentId', '566');
        $item->setReturnValue('accept', true);

        $parentItem = new MockDocman_Folder($this);
        
        $if = new MockDocman_ItemFactory($this);
        $if->setReturnValue('getItemFromDb', $item, array(344));
        $if->setReturnValue('getItemFromDb', $parentItem, array(566));

        $if->setReturnValueAt(0, 'getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        
        $actions = new Docman_ActionsTest($this);
        $actions->_controler = $ctrl;
        $actions->setReturnValue('_getItemFactory', $if);
        $actions->expectOnce('_getItemFactory', array(102));

        $actions->setReturnValue('_getEventManager', new MockEventManager($this));
        
        // Run test
        $actions->deleteVersion();
    }
}
?>
