<?php
/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
 * Copyright Enalean, 2017. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generatePartial('Docman_NotificationsManager_Move', 'Docman_NotificationsManager_MoveTestVersion',
    array(
        '_groupGetObject',
        '_getItemFactory',
        '_getUserManager',
        '_getPermissionsManager',
        '_getDocmanPath',
        '_buildMessage',
    )
);

Mock::generate('DataAccessResult');

Mock::generate('BaseLanguage');

Mock::generate('Group');

Mock::generate('Feedback');

Mock::generate('PFUser');

Mock::generate('UserManager');

Mock::generate('Docman_ItemFactory');

Mock::generate('Docman_Item');

Mock::generate('Docman_PermissionsManager');

Mock::generate('Docman_Path');

class NotificationsManager_MoveTest extends TuleapTestCase
{
    var $groupId;

    /**
     * @var Tuleap\Mail\MailFilter
     */
    private $mail_filter;

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['sys_noreply'] = 'norelpy@codendi.org';
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', '/tuleap');
        $this->mail_filter = mock('Tuleap\Mail\MailFilter');
    }

    public function tearDown()
    {
        unset($GLOBALS['sys_noreply']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    function testNotifications() {
        // {{{ Listener cannot read moved item
        // We expect no notification
        $dr = 0;
        for($br = 0 ; $br <= 1 ; ++$br) {
            for($cr = 0 ; $cr <= 1 ; ++$cr) {
                for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                    for ($lc = 0 ; $lc <= 1 ; ++$lc) {
                        for ($ld = 0 ; $ld <= 1 ; ++$ld) {
                            $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, 'none');
                        }
                    }
                }
            }
        }
        //}}}
        // {{{ Listener can read moved item
        $dr = 1;
            // {{{ Listener cannot read old parent
            $br = 0;
                // {{{ Listener cannot read new parent
                // We expect no notification
                $cr = 0;
                for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                    for ($lc = 0 ; $lc <= 1 ; ++$lc) {
                        for ($ld = 0 ; $ld <= 1 ; ++$ld) {
                            $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, 'none');
                        }
                    }
                }
                //}}}
                // {{{ Listener can read new parent
                // => A readable item is moved from an unreadable parent to a readable one
                $cr = 1;
                    //{{{ Do not listen item but maybe its parent ?
                    $ld = 0;
                    // No listeners, no notification
                    $this->_runTest($dr, $br, $cr, 0, 0, $ld, 'none');
                    // Only old parent is listened (but still unreadable), no notification
                    $this->_runTest($dr, $br, $cr, 1, 0, $ld, 'none');
                        // {{{ new parent is listened, we receive a notification without b because it is still unreadable
                        for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                            $this->_runTest($dr, $br, $cr, $lb, 1, $ld, 'to_wo_b');
                        }
                        //}}}
                    //}}}

                    //{{{ If we listen item, we receive a notification about item ("has been moved to c")
                    $ld = 1;
                    for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                        for ($lc = 0 ; $lc <= 1 ; ++$lc) {
                            $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, 'item');
                        }
                    }
                    //}}}
                //}}}
            //}}}
            // {{{ Listener can read old parent
            $br = 1;
                // {{{ Listener cannot read new parent
                // We have to send notifications only when old parent or item is listened
                $cr = 0;
                for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                    for ($lc = 0 ; $lc <= 1 ; ++$lc) {
                        for ($ld = 0 ; $ld <= 1 ; ++$ld) {
                            $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, $lb || $ld ? 'from_wo_c' : 'none');
                        }
                    }
                }
                //}}}
                // {{{ Listener can read new parent
                $cr = 1;
                    // {{{ Moved item is listened, notification on item
                    $ld = 1;
                    for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                        for ($lc = 0 ; $lc <= 1 ; ++$lc) {
                            $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, 'item');
                        }
                    }
                    //}}}
                    //{{{ Moved item is not listened
                    $ld = 0;
                        // {{{ new parent is listened, notification 'to'
                        $lc = 1;
                        for ($lb = 0 ; $lb <= 1 ; ++$lb) {
                            $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, 'to');
                        }
                        //}}}
                        // {{{ new parent is not listened
                        $lc = 0;
                        //Old parent is listened, 'from' notification
                        $this->_runTest($dr, $br, $cr, 1, $lc, $ld, 'from');
                        //No listener, no notification
                        $this->_runTest($dr, $br, $cr, 0, $lc, $ld, 'none');
                        //}}}
                    //}}}
                //}}}
            //}}}
        //}}}
    }
    /**
    *        A
    *        |-- B
    *        | +-+-----+
    *        | | `-- D |
    *        | +-------+ \
    *        `-- C        |
    *             <-------²
    *
    *        D is moved from B to C
    *
    * @param dr   d is readable 0|1
    * @param br   b is readable 0|1
    * @param cr   c is readable 0|1
    * @param lb   b is listened 0|1
    * @param lc   c is listened 0|1
    * @param ld   d is listened 0|1
    * @param res  expected result: item | from | from_wo_c | to | to_wo_b | none
    * @param msg  message to display if the test fail
    */
    function _runTest($dr, $br, $cr, $lb, $lc, $ld, $res, $msg = "%s") {
        $msg = "[$dr, $br, $cr, $lb, $lc, $ld, $res] ". $msg;

        $a = new MockDocman_Item();
        $a->setReturnValue('getId', 'a');
        $a->setReturnValue('getParentId', 0);
        $b = new MockDocman_Item();
        $b->setReturnValue('getId', 'b');
        $b->setReturnValue('getParentId', 'a');
        $c = new MockDocman_Item();
        $c->setReturnValue('getId', 'c');
        $c->setReturnValue('getParentId', 'a');
        $d = new MockDocman_Item();
        $d->setReturnValue('getId', 'd');
        $d->setReturnValue('getParentId', 'b');

        $group_id = 101;
        $project  = aMockProject()->withId($group_id)->build();

        $user = mock('PFUser');
        $user->setReturnValue('getId', 'user');
        $listener = mock('PFUser');
        $listener->setReturnValue('getId', 'listener');

        $feedback = new Feedback();

        $item_factory = new MockDocman_ItemFactory();
        $item_factory->setReturnReference('getItemFromDb', $a, array($a->getId()));
        $item_factory->setReturnReference('getItemFromDb', $b, array($b->getId()));
        $item_factory->setReturnReference('getItemFromDb', $c, array($c->getId()));
        $item_factory->setReturnReference('getItemFromDb', $d, array($d->getId()));

        $user_manager = new MockUserManager();
        $user_manager->setReturnReference('getUserById', $user, array($user->getId()));
        $user_manager->setReturnReference('getUserById', $listener, array($listener->getId()));

        $permissions_manager = new MockDocman_PermissionsManager();
        $permissions_manager->setReturnValue('userCanRead',   true, array(&$listener, $a->getId()));
        $permissions_manager->setReturnValue('userCanAccess', true, array(&$listener, $a->getId()));
        $permissions_manager->setReturnValue('userCanRead',   $cr, array(&$listener, $c->getId()));
        $permissions_manager->setReturnValue('userCanAccess', $cr, array(&$listener, $c->getId()));
        $permissions_manager->setReturnValue('userCanRead',   $br, array(&$listener, $b->getId()));
        $permissions_manager->setReturnValue('userCanAccess', $br, array(&$listener, $b->getId()));
        $permissions_manager->setReturnValue('userCanRead',   $dr, array(&$listener, $d->getId()));
        $permissions_manager->setReturnValue('userCanAccess', $dr && $br, array(&$listener, $d->getId()));

        $dar_d = new MockDataAccessResult();
        if ($ld) {
            $dar_d->setReturnValueAt(0, 'valid', true);
            $dar_d->setReturnValueAt(1, 'valid', false);
            $dar_d->setReturnValue('current', array('user_id' => $listener->getId(), 'item_id' => $d->getId()));
        } else {
            $dar_d->setReturnValue('valid', false);
        }

        $dar_c = new MockDataAccessResult();
        if ($lc) {
            $dar_c->setReturnValueAt(0, 'valid', true);
            $dar_c->setReturnValueAt(1, 'valid', false);
            $dar_c->setReturnValue('current', array('user_id' => $listener->getId(), 'item_id' => $c->getId()));
        } else {
            $dar_c->setReturnValue('valid', false);
        }

        $dar_b = new MockDataAccessResult();
        if ($lb) {
            $dar_b->setReturnValueAt(0, 'valid', true);
            $dar_b->setReturnValueAt(1, 'valid', false);
            $dar_b->setReturnValue('current', array('user_id' => $listener->getId(), 'item_id' => $b->getId()));
        } else {
            $dar_b->setReturnValue('valid', false);
        }

        $docman_path = new MockDocman_Path();

        $dao = mock('Tuleap\Docman\Notifications\Dao');
        stub($dao)->searchUserIdByObjectIdAndType($d->getId(), 'plugin_docman')->returns($dar_d);
        $dao->setReturnValue('searchUserIdByObjectIdAndType', $dar_d, array($d->getId(), 'plugin_docman'));
        $dao->setReturnValue('searchUserIdByObjectIdAndType', $dar_c, array($c->getId(), 'plugin_docman'));
        $dao->setReturnValue('searchUserIdByObjectIdAndType', $dar_b, array($b->getId(), 'plugin_docman'));

        $dnmm = new Docman_NotificationsManager_MoveTestVersion();
        $dnmm->setReturnReference('_groupGetObject', $project);
        $dnmm->setReturnReference('_getItemFactory', $item_factory);
        $dnmm->setReturnReference('_getUserManager', $user_manager);
        $dnmm->setReturnReference('_getPermissionsManager', $permissions_manager);
        $dnmm->setReturnReference('_getDocmanPath', $docman_path);

        if ($res != 'none') {
            $dnmm->expectOnce('_buildMessage', false, $msg);
        } else {
            $dnmm->expectNever('_buildMessage', $msg);
        }

        $mail_builder = new MailBuilder(TemplateRendererFactory::build(), $this->mail_filter);

        //C'est parti
        $dnmm->__construct(
            $project,
            'my_url',
            $feedback,
            $mail_builder,
            $dao
        );
        $dnmm->somethingHappen('plugin_docman_event_move', array(
            'group_id' => $group_id,
            'item'    => &$d,
            'parent'  => &$c,
            'user'    => &$user)
        );

    }
}
