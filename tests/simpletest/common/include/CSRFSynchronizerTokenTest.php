<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once('common/include/CSRFSynchronizerToken.class.php');
Mock::generatePartial('CSRFSynchronizerToken', 'CSRFSynchronizerTokenTestVersion', array('getUser'));

require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/include/Codendi_Request.class.php');
Mock::generate('Codendi_Request');
require_once('common/layout/Layout.class.php');
Mock::generate('Layout');

class CSRFUserTestVersion_MockPreferences extends MockPFUser {
    protected $UserTestVersion_MockPreferences_hash = array();
    
    public function getPreference($key) {
        if (isset($this->UserTestVersion_MockPreferences_hash[$key])) {
            return $this->UserTestVersion_MockPreferences_hash[$key];
        }
        return false;
    }
    
    public function setPreference($key, $value) {
        $this->UserTestVersion_MockPreferences_hash[$key] = $value;
    }

    public function delPreference($key, $value) {
        if (isset($this->UserTestVersion_MockPreferences_hash[$key])) {
            unset($this->UserTestVersion_MockPreferences_hash[$key]);
        }
    }
}

class CSRFSynchronizerTokenTest extends UnitTestCase {
    
    public function setUp() {
        $GLOBALS['Response'] = new MockLayout();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }
    
    public function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }
    
    function testSynchronizerToken_valid() {
        $user = new CSRFUserTestVersion_MockPreferences($this);
        $user->setReturnValue('getSessionHash', 123);
        $user->setReturnValueAt(3, 'getSessionHash', 456);
        
        $token1 = new CSRFSynchronizerTokenTestVersion($this);
        $token1->setReturnReference('getUser', $user);
        $token1->__construct('/path/to/url/1');
        $this->assertFalse($token1->isValid(''));
        $this->assertFalse($token1->isValid(md5('meh')));
        $token1_value = $token1->getToken();
        $this->assertTrue($token1->isValid($token1_value));
        
        $token2 = new CSRFSynchronizerTokenTestVersion($this);
        $token2->setReturnReference('getUser', $user);
        $token2->__construct('/path/to/url/2');
        $token2_value = $token2->getToken();
        $this->assertTrue($token1->isValid($token1_value));
        $this->assertFalse($token1->isValid($token2_value));
        $this->assertFalse($token2->isValid($token1_value));
        $this->assertTrue($token2->isValid($token2_value));
        
        $token3 = new CSRFSynchronizerTokenTestVersion($this);
        $token3->setReturnReference('getUser', $user);
        $token3->__construct('/path/to/url/1'); //same url as token1
        $token3_value = $token3->getToken();
        $this->assertEqual($token3_value, $token1_value);
        $this->assertTrue($token1->isValid($token3_value));
        $this->assertTrue($token3->isValid($token1_value));
        
        //Now we change the session hash. it should invalidate the previously generated token
        $newtoken1 = new CSRFSynchronizerTokenTestVersion($this);
        $newtoken1->setReturnReference('getUser', $user);
        $newtoken1->__construct('/path/to/url/1');
        $this->assertFalse($newtoken1->isValid($token1_value));
    }
    
    function testSynchronizerToken_check() {
        
        $GLOBALS['Response']->expectCallCount('addFeedback', 2);
        $GLOBALS['Response']->expectAt(0, 'redirect', array('/path/to/redirect'));
        $GLOBALS['Response']->expectAt(1, 'redirect', array('/path/to/url/3'));
        $GLOBALS['Response']->expectCallCount('redirect', 2);
        $user = new CSRFUserTestVersion_MockPreferences($this);
        $user->setReturnValue('getSessionHash', 123);
        
        $token1 = new CSRFSynchronizerTokenTestVersion($this);
        $token1->setReturnReference('getUser', $user);
        $token1->__construct('/path/to/url/1', 'challenge1');
        
        $token2 = new CSRFSynchronizerTokenTestVersion($this);
        $token2->setReturnReference('getUser', $user);
        $token2->__construct('/path/to/url/2', 'challenge2');
        
        $token3 = new CSRFSynchronizerTokenTestVersion($this);
        $token3->setReturnReference('getUser', $user);
        $token3->__construct('/path/to/url/3', 'challenge3');
        
        
        $request = new MockCodendi_Request();
        $request->setReturnValue('get', $token1->getToken(), array('challenge1'));
        $request->setReturnValue('get', md5('pouet'), array('challenge2'));
        $request->setReturnValue('get', false, array('challenge3'));
        $request->setReturnValue('existAndNonEmpty', true, array('challenge1'));
        $request->setReturnValue('existAndNonEmpty', true, array('challenge2'));
        $request->setReturnValue('existAndNonEmpty', false, array('challenge3'));
        
        $token1->check('/should/not/be/redirected', $request); //token1 is good
        $token2->check('/path/to/redirect', $request);         //token2 is invalid
        $token3->check(null, $request);                        //token3 is invalid. return to default url
        
    }
    
    function testSynchronizerToken_htmlinput() {
        $user  = new CSRFUserTestVersion_MockPreferences($this);
        $user->setReturnValue('getSessionHash', 123);
        
        $token1 = new CSRFSynchronizerTokenTestVersion($this);
        $token1->setReturnReference('getUser', $user);
        $token1->__construct('/path/to/url/1');
        
        $token2 = new CSRFSynchronizerTokenTestVersion($this);
        $token2->setReturnReference('getUser', $user);
        $token2->__construct('/path/to/url/2', 'pouet');
        
        $this->assertEqual('<input type="hidden" name="challenge" value="'. $token1->getToken() .'" />', $token1->fetchHTMLInput());
        $this->assertEqual('<input type="hidden" name="pouet" value="'. $token2->getToken() .'" />', $token2->fetchHTMLInput());
    }
}
?>
