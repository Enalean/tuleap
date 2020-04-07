<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved.
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

class Codendi_SessionTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    /**
     * @var array
     */
    private $pseudo_php_session;
    /**
     * @var Codendi_Session
     */
    private $codendi_session;

    protected function setUp(): void
    {
        $this->pseudo_php_session                        = [];
        $this->codendi_session                           = new Codendi_Session($this->pseudo_php_session);
        $this->pseudo_php_session['Codendi_SessionTest'] = [];
        $this->codendi_session->setSessionNamespace($this->pseudo_php_session['Codendi_SessionTest']);
        $this->codendi_session->setSessionNamespacePath('.Codendi_SessionTest');
    }

    public function testGetNamespaceHappyPath()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['picsou'];
        $namespace                         = 'fifi.riri.loulou';
        $session_namespace                 = &$this->codendi_session->getNamespace($namespace);
        $this->assertContains('picsou', $session_namespace);
    }

    public function testGetNamespaceOneLevel()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['picsou'];
        $namespace                         = 'fifi';
        $session_namespace                 = &$this->codendi_session->getNamespace($namespace);
        $this->assertSame($session_namespace['riri']['loulou'][0], 'picsou');
    }

    public function testGetNamespaceEmpty()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['picsou'];
        $namespace                         = '';
        $session_namespace                 = &$this->codendi_session->getNamespace($namespace);
        $this->assertSame($session_namespace, $session);
    }

    public function testGetNamespaceCreatePath()
    {
        $session   = &$this->codendi_session->getSessionNamespace();
        $namespace = 'fifi.riri.loulou';
        $this->codendi_session->getNamespace($namespace, true);
        $this->assertTrue(isset($session['fifi']['riri']['loulou']));
    }

    public function testSet()
    {
        $session = &$this->codendi_session->getSessionNamespace();
        $this->codendi_session->set('fifi.riri.loulou.oncle', 'picsou');
        $expected = ['fifi' => ['riri' => ['loulou' => ['oncle' => 'picsou']]]];
        $this->assertSame($expected, $session);
    }

    public function testRemoveWithKey()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['oncle' => 'picsou'];
        $this->codendi_session->remove('fifi.riri.loulou', 'oncle');
        $this->assertFalse(isset($session['fifi']['riri']['loulou']['oncle']));
    }

    public function testRemoveNoKey()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['oncle' => 'picsou'];
        $this->codendi_session->remove('fifi.riri.loulou.oncle');
        $this->assertSame($session['fifi']['riri']['loulou']['oncle'], '');
    }

    public function testCleanNamespace()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['oncle' => 'picsou'];
        $this->codendi_session->cleanNamespace();
        $this->assertSame($session, '');
    }

    public function testGetNoKey()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['oncle' => 'picsou'];
        $value                             = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertSame($value, ['oncle' => 'picsou']);
    }

    public function testGetWithKey()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['oncle' => 'picsou'];
        $value                             = $this->codendi_session->get('fifi.riri.loulou', 'oncle');
        $this->assertSame($value, 'picsou');
    }

    public function testGetDoesNotExist()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = 'tutu';
        $value                             = $this->codendi_session->get('fifi.riri.toto');
        $this->assertSame($value, null);
    }

    public function testGetNotArrayValue()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = 'tutu';
        $value                             = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertSame($value, 'tutu');
    }

    public function testGetArrayValue()
    {
        $session                           = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']['loulou'] = ['tutu'];
        $value                             = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertSame($value, ['tutu']);
    }

    public function testGetUncompletePath()
    {
        $session                 = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri'] = 'tutu';
        $value                   = $this->codendi_session->get('fifi.riri.loulou');
        $this->assertNull($value);
    }

    public function testChangeSessionNamespaceRelativeAlreadyExists()
    {
        $session = &$this->codendi_session->getSessionNamespace();
        $session = ['fifi' => ['riri' => ['loulou' => ['oncle' => 'picsou']]]];
        $this->codendi_session->changeSessionNamespace('fifi.riri');
        $new_session = &$this->codendi_session->getSessionNamespace();
        $this->assertSame($new_session, ['loulou' => ['oncle' => 'picsou']]);
    }

    public function testChangeSessionNamespaceRelativeDoesntExist()
    {
        $this->codendi_session->changeSessionNamespace('fifi.riri');
        $this->codendi_session->changeSessionNamespace('.Codendi_SessionTest');
        $new_session = $this->codendi_session->getSessionNamespace();
        $this->assertSame($new_session, ['fifi' => ['riri' => []]]);
    }

    public function testChangeSessionNamespaceAbsolute()
    {
        $this->pseudo_php_session['Codendi_SessionTest']['toto'] = 'labricot';
        $session                                                 = &$this->codendi_session->getSessionNamespace();
        $session['fifi']['riri']                                 = 'loulou';
        $this->codendi_session->changeSessionNamespace('.Codendi_SessionTest.toto');
        $new_session = &$this->codendi_session->getSessionNamespace();
        $this->assertSame($new_session, 'labricot');
    }

    public function testChangeSessionNamespaceGotoRoot()
    {
        $session = &$this->codendi_session->getSessionNamespace();
        $this->codendi_session->changeSessionNamespace('.');
        $session_bis = &$this->codendi_session->getSessionNamespace();
        $this->assertSame($session, []);
        $this->assertSame($session_bis, ['Codendi_SessionTest' => []]);
    }

    public function testOverloading()
    {
        $pseudo_php_session = [];
        $session            = new Codendi_Session($pseudo_php_session);
        $this->assertFalse(isset($session->riri));
        $this->assertFalse(isset($pseudo_php_session['riri']));
        $session->riri = 'fifi';
        $this->assertTrue(isset($session->riri));
        $this->assertTrue(isset($pseudo_php_session['riri']));
        $this->assertSame($pseudo_php_session['riri'], $session->riri);
        $this->assertSame($pseudo_php_session['riri'], 'fifi');
        unset($session->riri);
        $this->assertFalse(isset($session->riri));
        $this->assertFalse(isset($pseudo_php_session['riri']));
    }

    public function testItRaisesAnErrorWhenTryingToUseAStringAsAStringOffset()
    {
        $this->expectWarning();

        $pseudo_php_session = [];
        $session            = new Codendi_Session($pseudo_php_session);
        $session->changeSessionNamespace('riri');
        $session->fifi = 'blop';
        $session->changeSessionNamespace('.riri.fifi');

        $session->tutu = 'first';
    }

    public function testOverloadingNamespace()
    {
        $pseudo_php_session = [];
        $session            = new Codendi_Session($pseudo_php_session);
        $this->assertFalse(isset($session->riri));
        $this->assertFalse(isset($pseudo_php_session['riri']));
        $session->changeSessionNamespace('riri');
        $this->assertFalse(isset($session->riri));
        $this->assertTrue(isset($pseudo_php_session['riri']));

        $session->fifi = 'loulou';
        $this->assertSame($pseudo_php_session['riri']['fifi'], 'loulou');
        $this->assertSame($session->fifi, 'loulou');

        $session->mickey = ['friend' => 'pluto'];
        $session->changeSessionNamespace('mickey');
        $this->assertSame($session->friend, 'pluto');
        $this->assertSame($session->get('friend'), 'pluto');
        $this->assertSame($pseudo_php_session['riri']['mickey']['friend'], 'pluto');

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

        // Check behaviour __get and references.
        // The expected error (notice) is:
        // Unexpected PHP error [Indirect modification of overloaded property Codendi_Session::$fifi has no effect
        // the workaround:
        //here you get the reference
        $b =& $session->get('fifi');
        $b = 66;
        $this->assertSame($session->fifi, 66);
        // }}}
    }
}
