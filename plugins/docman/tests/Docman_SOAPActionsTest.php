<?php
/**
 * Originally written by Clément Plantier, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/language/BaseLanguage.class.php');
$GLOBALS['Language'] = new BaseLanguage();

require_once(dirname(__FILE__).'/../include/Docman_SOAPController.class.php');
require_once('common/include/SOAPRequest.class.php');
require_once('common/include/Feedback.class.php');
require_once('common/user/UserManager.class.php');
require_once('common/user/User.class.php');
require_once('common/event/EventManager.class.php');

Mock::generate('Docman_SOAPController');
Mock::generate('Feedback');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_File');
Mock::generate('Docman_Version');
Mock::generate('Docman_FileStorage');
Mock::generate('UserManager');
Mock::generate('User');
Mock::generate('EventManager');
Mock::generatePartial('Docman_SOAPActions', 'Docman_SOAPActions_Test', array(
                                                                           '_getItemFactory',
                                                                           '_getFileStorage',
                                                                           '_checkOwnerChange',
                                                                           '_getUserManagerInstance',
                                                                       ));

/**
 * Unit tests for Docman_SOAPActions
 */
class Docman_SOAPActionsTest extends UnitTestCase {
    private $MD5Map;
    private $itemFactory;
    private $action;
    
    function Docman_SOAPActionsTest($name = 'Docman_Actions test') {
        $this->UnitTestCase($name);
    }
    
    public function setUp() {
        $feedback = new MockFeedback();
        
        $controller = new MockDocman_SOAPController();
        $controller->feedback = $feedback;
        
        $version = new MockDocman_Version();
        $version->setReturnValue('getNumber', 0);
        
        $itemFactory = new MockDocman_ItemFactory();
        $storage = new MockDocman_FileStorage();
        
        // MD5Map: id => md5sum
        $this->MD5Map = array(128000 => '99999999999999999999999999999999');
        
        foreach ($this->MD5Map as $itemId => $md5) {
            $file = new MockDocman_File();
            $file->setReturnValue('getID', $itemId);
            $file->setReturnValue('getCurrentVersion', $version);
            $itemFactory->setReturnValue('getItemFromDb', $file, array($itemId));
            $itemFactory->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE, array($file));
            $storage->setReturnValue('getFileMD5sum', $md5, array('*', $itemId, '*'));
        }
        
        $user = new MockUser();
        
        $userManager = new MockUserManager();
        $userManager->setReturnValue('getUserById', $user);
        
        $action = new Docman_SOAPActions_Test();
        $action->setReturnValue('_getItemFactory', $itemFactory);
        $action->setReturnValue('_getFileStorage', $storage);
        $action->setReturnValue('_getUserManagerInstance', $userManager);
        $action->setReturnValue('_checkOwnerChange', 101, array('*', '*'));
        $action->Docman_SOAPActions($controller);
        
        $eventManager = new MockEventManager();
        $action->event_manager = $eventManager;
        
        $this->action = $action;
        $this->itemFactory = $itemFactory;
    }
    
    /**
     * Nominal case: getFileMD5sum is called with a correct file ID
     */
    public function testGetFileMD5sumNominal() {
        $action = $this->action;
        
        $params = array(
              'item_id' => 128000,
              'group_id'=> 2,
          );
        $request = new SOAPRequest($params);
        $action->getControler()->request = $request;
            
        $action->getFileMD5sum();
        $this->assertEqual($action->getControler()->_viewParams['action_result'], $this->MD5Map[$params['item_id']]);
    }
    
    /**
     * Error case: getFileMD5sum with no item ID
     */
    public function testGetFileMD5sumNoItemError() {
        $action = $this->action;
        
        $params = array();
        $request = new SOAPRequest($params);
        $action->getControler()->request = $request;
        
        $action->getControler()->feedback->expectOnce('log', array('error', '*'));
        
        $action->getFileMD5sum();
    }
    
    /**
     * Error case: getFileMD5sum is called with an incorrect ID
     */
    public function testGetFileMD5sumItemNotFoundError() {
        $action = $this->action;
        
        $params = array('item_id' => 0);
        $request = new SOAPRequest($params);
        $action->getControler()->request = $request;
        
        $action->getControler()->feedback->expectOnce('log', array('error', '*'));
        
        $action->getFileMD5sum();
    }
    
    /**
     * update test with item owner changed
     */
    public function testUpdate() {
        $action = $this->action;
        
        $params = array(
                      'item' => array(
                                    'owner' => 'testuser',
                                    'id'    => 128000,
                                ),
                      'group_id'=> 2,
                  );
        $request = new SOAPRequest($params);
        $action->getControler()->request = $request;
        
        $action->expectOnce('_checkOwnerChange', array($params['item']['owner'], '*'));
        $this->itemFactory->expectOnce('update');
        $action->event_manager->expectAt(0, 'processEvent', array('plugin_docman_event_metadata_update', '*'));
        $action->event_manager->expectAt(1, 'processEvent', array('send_notifications', '*'));
        
        $action->update();
    }
}


?>