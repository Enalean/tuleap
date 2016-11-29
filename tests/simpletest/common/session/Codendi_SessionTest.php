<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('common/session/Codendi_Session.class.php');

class Codendi_SessionTest extends TuleapTestCase
{
    private $original_session;

    public function setUp()
    {
        parent::setUp();
        $this->codendi_session           = new Codendi_Session();
        $this->original_session          = $_SESSION;
        $_SESSION                        = array();
        $_SESSION['Codendi_SessionTest'] = array();
        $this->codendi_session->setSessionNamespace($_SESSION['Codendi_SessionTest']);
        $this->codendi_session->setSessionNamespacePath('.Codendi_SessionTest');
    }

    public function tearDown()
    {
        parent::tearDown();
        $_SESSION = $this->original_session;
    }

    public function test_getNamespace_HappyPath() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('picsou');
        $namespace = 'fifi.riri.loulou';
        $session_namespace = &$this->codendi_session->getNamespace($namespace);
        $this->assertTrue(in_array('picsou', $session_namespace));
    }

    public function test_getNamespace_OneLevel() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('picsou');
        $namespace = 'fifi';
        $session_namespace = &$this->codendi_session->getNamespace($namespace);
        $this->assertEqual( $session_namespace['riri']['loulou'][0] ,  'picsou');
    }

    public function test_getNamespace_Empty() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('picsou');
        $namespace = '';
        $msg       = '';
        $session_namespace = &$this->codendi_session->getNamespace($namespace);
        $this->assertEqual( $session_namespace, $session );
    }

    public function test_getNamespace_createPath() {
        $session = &$this->codendi_session->getSessionNamespace();
        $namespace = 'fifi.riri.loulou';
        $session_namespace = &$this->codendi_session->getNamespace($namespace, true);
        $this->assertTrue( isset($session['fifi']['riri']['loulou']) );
    }

    public function test_set() {
        $session = &$this->codendi_session->getSessionNamespace();
        $this->codendi_session->set('fifi.riri.loulou.oncle', 'picsou');
        $expected = array ('fifi'=>array('riri'=>array('loulou'=>array ('oncle' => 'picsou'))));
        $this->assertEqual($expected, $session);
    }

    public function test_remove_withKey() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('oncle'=>'picsou');
        $this->codendi_session->remove('fifi.riri.loulou', 'oncle');
        $this->assertFalse( isset($session['fifi']['riri']['loulou']['oncle']));
    }

    public function test_remove_noKey() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('oncle'=>'picsou');
        $this->codendi_session->remove('fifi.riri.loulou.oncle');
        $this->assertTrue( $session['fifi']['riri']['loulou']['oncle'] === '' );
    }

    public function test_cleanNamespace() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('oncle'=>'picsou');
        $this->codendi_session->cleanNamespace();
        $this->assertTrue( $session === '');
    }

    public function test_get_noKey() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('oncle'=>'picsou');
        $value = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertEqual($value, array('oncle'=>'picsou') );
    }

    public function test_get_withKey() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('oncle'=>'picsou');
        $value = $this->codendi_session->get('fifi.riri.loulou', 'oncle');
        $this->assertEqual($value, 'picsou' );
    }

    public function test_get_doesnotExist() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = 'tutu';
        $value = $this->codendi_session->get('fifi.riri.toto');
        $this->assertEqual($value, null);
    }

    public function test_get_notArrayValue() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = 'tutu';
        $value = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertEqual($value, 'tutu');
    }

    public function test_getArrayValue() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = array('tutu');
        $value = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertEqual($value, array('tutu'));
    }

   public function test_get_uncompletePath() {
        $session = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri'] = 'tutu';
        $value = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertEqual($value, null);
   }

   public function test_changeSessionNamespace_Relative_AlreadyExists() {
         $session = &$this->codendi_session->getSessionNamespace();
         $session = array ('fifi'=>array('riri'=>array('loulou'=>array ('oncle' => 'picsou'))));
         $this->codendi_session->changeSessionNamespace('fifi.riri');
         $new_session = &$this->codendi_session->getSessionNamespace();
         $this->assertEqual($new_session, array('loulou'=>array('oncle'=>'picsou')));
    }

    public function test_changeSessionNamespace_Relative_DoesntExist() {
         $session = &$this->codendi_session->getSessionNamespace();
         $this->codendi_session->changeSessionNamespace('fifi.riri');
         $this->codendi_session->changeSessionNamespace('.Codendi_SessionTest');
         $new_session = $this->codendi_session->getSessionNamespace();
         $this->assertEqual($new_session, array('fifi'=>array('riri'=>array())));
    }

     public function test_changeSessionNamespace_Absolute() {
         $_SESSION['Codendi_SessionTest']['toto'] = 'labricot';
         $session = &$this->codendi_session->getSessionNamespace();
         $session['fifi']['riri'] = 'loulou';
         $this->codendi_session->changeSessionNamespace('.Codendi_SessionTest.toto');
         $new_session = &$this->codendi_session->getSessionNamespace();
         $this->assertEqual($new_session, 'labricot');
         unset($_SESSION['Codendi_SessionTest']);
    }

    public function test_changeSessionNamespace_gotoRoot() {
         $session = &$this->codendi_session->getSessionNamespace();
         $this->codendi_session->changeSessionNamespace('.');
         $session_bis = &$this->codendi_session->getSessionNamespace();
         $this->assertEqual($session, array());
         $this->assertEqual($session_bis, array('Codendi_SessionTest'=>array()));
    }


    public function test_Overloading() {
        $pseudo_php_session = array();
        $session = new Codendi_Session($pseudo_php_session);
        $this->assertFalse(isset($session->riri));
        $this->assertFalse(isset($pseudo_php_session['riri']));
        $session->riri = 'fifi';
        $this->assertTrue(isset($session->riri));
        $this->assertTrue(isset($pseudo_php_session['riri']));
        $this->assertEqual($pseudo_php_session['riri'], $session->riri);
        $this->assertEqual($pseudo_php_session['riri'], 'fifi');
        unset($session->riri);
        $this->assertFalse(isset($session->riri));
        $this->assertFalse(isset($pseudo_php_session['riri']));
    }

    public function testItRaisesAnErrorWhenTryingToUseAStringAsAStringOffset() {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $pseudo_php_session = array();
            $session = new Codendi_Session($pseudo_php_session);
            $session->changeSessionNamespace('riri');
            $session->fifi = 'blop';
            $session->changeSessionNamespace('.riri.fifi');

            $this->expectError();
            $session->tutu = 'fist';
        }
    }

    public function test_Overloading_namespace() {
        $pseudo_php_session = array();
        $session = new Codendi_Session($pseudo_php_session);
        $this->assertFalse(isset($session->riri));
        $this->assertFalse(isset($pseudo_php_session['riri']));
        $session->changeSessionNamespace('riri');        
        $this->assertFalse(isset($session->riri));
        $this->assertTrue(isset($pseudo_php_session['riri']));
        
        $session->fifi = 'loulou';
        $this->assertEqual($pseudo_php_session['riri']['fifi'], 'loulou');
        $this->assertEqual($session->fifi, 'loulou');

        $session->mickey = array('friend' => 'pluto');
        $session->changeSessionNamespace('mickey');
        $this->assertEqual($session->friend, 'pluto');
        $this->assertEqual($session->get('friend'), 'pluto');
        $this->assertEqual($pseudo_php_session['riri']['mickey']['friend'], 'pluto');

        $session->changeSessionNamespace('.');
        $this->assertNull($session->friend);
        $this->assertTrue(isset($session->riri));

        $session->changeSessionNamespace('.riri');
        $this->assertTrue(isset($session->mickey));

        $session->changeSessionNamespace('.');
        $session->changeSessionNamespace('riri.mickey');
        $this->assertTrue(isset($session->friend));

        $session->changeSessionNamespace('.riri.mickey');
        $this->assertTrue(isset($session->friend));

        // {{{ PHP prevents us to do thing like that. Which is too bad
        $session->changeSessionNamespace('.riri');

        //
        // Check behaviour __get and references.
        // The expected error (notice) is:
        // Unexpected PHP error [Indirect modification of overloaded property Codendi_Session::$fifi has no effect
        if (version_compare(PHP_VERSION, '5.2.0', '>=')) { // the error is raised only for further php version. 5.1.6 is silent
            $this->expectError();
        }
        //here you get the variable value not the reference
        $a =& $session->fifi;
        $a = 66;
        $this->assertEqual($session->fifi, 'loulou');
        // the workaround:
        //here you get the reference
        $b =& $session->get('fifi');
        $b = 66;
        $this->assertEqual($session->fifi, 66);
        // }}}
    }
}

?>
