<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersUpdater;
use Tuleap\Templating\TemplateCache;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class NotificationsManager_MoveTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    /**
     * @var Tuleap\Mail\MailFilter
     */
    private $mail_filter;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_noreply', 'norelpy@example.com');
        ForgeConfig::set('codendi_dir', '/tuleap');
        $this->mail_filter = \Mockery::spy(\Tuleap\Mail\MailFilter::class);
    }

    public function testNotifications(): void
    {
        // {{{ Listener cannot read moved item
        // We expect no notification
        $dr = 0;
        for ($br = 0; $br <= 1; ++$br) {
            for ($cr = 0; $cr <= 1; ++$cr) {
                for ($lb = 0; $lb <= 1; ++$lb) {
                    for ($lc = 0; $lc <= 1; ++$lc) {
                        for ($ld = 0; $ld <= 1; ++$ld) {
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
        for ($lb = 0; $lb <= 1; ++$lb) {
            for ($lc = 0; $lc <= 1; ++$lc) {
                for ($ld = 0; $ld <= 1; ++$ld) {
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
        for ($lb = 0; $lb <= 1; ++$lb) {
            $this->_runTest($dr, $br, $cr, $lb, 1, $ld, 'to_wo_b');
        }
                        //}}}
                    //}}}

                    //{{{ If we listen item, we receive a notification about item ("has been moved to c")
                    $ld = 1;
        for ($lb = 0; $lb <= 1; ++$lb) {
            for ($lc = 0; $lc <= 1; ++$lc) {
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
        for ($lb = 0; $lb <= 1; ++$lb) {
            for ($lc = 0; $lc <= 1; ++$lc) {
                for ($ld = 0; $ld <= 1; ++$ld) {
                    $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, $lb || $ld ? 'from_wo_c' : 'none');
                }
            }
        }
                //}}}
                // {{{ Listener can read new parent
                $cr = 1;
                    // {{{ Moved item is listened, notification on item
                    $ld = 1;
        for ($lb = 0; $lb <= 1; ++$lb) {
            for ($lc = 0; $lc <= 1; ++$lc) {
                $this->_runTest($dr, $br, $cr, $lb, $lc, $ld, 'item');
            }
        }
                    //}}}
                    //{{{ Moved item is not listened
                    $ld = 0;
                        // {{{ new parent is listened, notification 'to'
                        $lc = 1;
        for ($lb = 0; $lb <= 1; ++$lb) {
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
    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _runTest($dr, $br, $cr, $lb, $lc, $ld, $res, $msg = "%s"): void
    {
        $msg = "[$dr, $br, $cr, $lb, $lc, $ld, $res] " . $msg;

        $a = \Mockery::spy(\Docman_Item::class);
        $a->shouldReceive('getId')->andReturns(102);
        $a->shouldReceive('getParentId')->andReturns(0);
        $b = \Mockery::spy(\Docman_Item::class);
        $b->shouldReceive('getId')->andReturns(103);
        $b->shouldReceive('getParentId')->andReturns(102);
        $c = \Mockery::spy(\Docman_Item::class);
        $c->shouldReceive('getId')->andReturns(104);
        $c->shouldReceive('getParentId')->andReturns(102);
        $d = \Mockery::spy(\Docman_Item::class);
        $d->shouldReceive('getId')->andReturns(105);
        $d->shouldReceive('getParentId')->andReturns(103);

        $group_id = 101;
        $project  = \Mockery::spy(\Project::class, ['getID' => $group_id, 'getUserName' => false, 'isPublic' => false]);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns('user');
        $listener = \Mockery::spy(\PFUser::class);
        $listener->shouldReceive('getId')->andReturns('listener');

        $feedback = new Feedback();

        $item_factory = \Mockery::spy(\Docman_ItemFactory::class);
        $item_factory->shouldReceive('getItemFromDb')->with($a->getId())->andReturns($a);
        $item_factory->shouldReceive('getItemFromDb')->with($b->getId())->andReturns($b);
        $item_factory->shouldReceive('getItemFromDb')->with($c->getId())->andReturns($c);
        $item_factory->shouldReceive('getItemFromDb')->with($d->getId())->andReturns($d);

        $user_manager = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('getUserById')->with($user->getId())->andReturns($user);
        $user_manager->shouldReceive('getUserById')->with($listener->getId())->andReturns($listener);

        $permissions_manager = \Mockery::spy(\Docman_PermissionsManager::class);
        $permissions_manager->shouldReceive('userCanRead')->with($listener, $a->getId())->andReturns(true);
        $permissions_manager->shouldReceive('userCanAccess')->with($listener, $a->getId())->andReturns(true);
        $permissions_manager->shouldReceive('userCanRead')->with($listener, $c->getId())->andReturns($cr);
        $permissions_manager->shouldReceive('userCanAccess')->with($listener, $c->getId())->andReturns($cr);
        $permissions_manager->shouldReceive('userCanRead')->with($listener, $b->getId())->andReturns($br);
        $permissions_manager->shouldReceive('userCanAccess')->with($listener, $b->getId())->andReturns($br);
        $permissions_manager->shouldReceive('userCanRead')->with($listener, $d->getId())->andReturns($dr);
        $permissions_manager->shouldReceive('userCanAccess')->with($listener, $d->getId())->andReturns($dr && $br);

        $dao = \Mockery::spy(\Tuleap\Docman\Notifications\UsersToNotifyDao::class);

        if ($ld) {
            $dao->shouldReceive('searchUserIdByObjectIdAndType')->with($d->getId(), 'plugin_docman')->andReturns(
                \TestHelper::arrayToDar(['user_id' => $listener->getId(), 'item_id' => $d->getId()])
            );
        } else {
            $dao->shouldReceive('searchUserIdByObjectIdAndType')->with($d->getId(), 'plugin_docman')->andReturns(
                \TestHelper::emptyDar()
            );
        }

        if ($lc) {
            $dao->shouldReceive('searchUserIdByObjectIdAndType')->with($c->getId(), 'plugin_docman')->andReturns(
                \TestHelper::arrayToDar(['user_id' => $listener->getId(), 'item_id' => $c->getId()])
            );
        } else {
            $dao->shouldReceive('searchUserIdByObjectIdAndType')->with($c->getId(), 'plugin_docman')->andReturns(
                \TestHelper::emptyDar()
            );
        }

        if ($lb) {
            $dao->shouldReceive('searchUserIdByObjectIdAndType')->with($b->getId(), 'plugin_docman')->andReturns(
                \TestHelper::arrayToDar(['user_id' => $listener->getId(), 'item_id' => $b->getId()])
            );
        } else {
            $dao->shouldReceive('searchUserIdByObjectIdAndType')->with($b->getId(), 'plugin_docman')->andReturns(
                \TestHelper::emptyDar()
            );
        }

        $docman_path = \Mockery::spy(\Docman_Path::class);

        $dnmm = \Mockery::mock(\Docman_NotificationsManager_Move::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $dnmm->shouldReceive('_groupGetObject')->andReturns($project);
        $dnmm->shouldReceive('_getItemFactory')->andReturns($item_factory);
        $dnmm->shouldReceive('_getUserManager')->andReturns($user_manager);
        $dnmm->shouldReceive('_getPermissionsManager')->andReturns($permissions_manager);
        $dnmm->shouldReceive('_getDocmanPath')->andReturns($docman_path);

        if ($res != 'none') {
        } else {
            $dnmm->shouldReceive('_buildMessage')->never();
        }

        $ugroups_to_notify_dao = \Mockery::spy(\Tuleap\Docman\Notifications\UgroupsToNotifyDao::class);
        $ugroups_to_notify_dao->shouldReceive('searchUgroupsByItemIdAndType')->andReturns(false);
        $docman_itemfactory      = \Mockery::spy(\Docman_ItemFactory::class);
        $ugroup_manager          = \Mockery::spy(\UGroupManager::class);
        $template_render_factory = Mockery::mock(TemplateRendererFactory::class);
        $template_cache          = Mockery::mock(TemplateCache::class);
        $template_render_factory->shouldReceive('build')->andReturn($template_cache);
        $template_rendder = Mockery::mock(TemplateRenderer::class);
        $template_render_factory->shouldReceive('getRenderer')->andReturn($template_rendder);

        $link_provider = Mockery::mock(ILinkUrlProvider::class);
        $link_provider->shouldReceive('getShowLinkUrl')->with($c)->andReturn();
        $link_provider->shouldReceive('getNotificationLinkUrl')->with($d)->andReturn();

        $mail_builder    = new MailBuilder($template_render_factory, $this->mail_filter);
        $users_retriever = new UsersRetriever(
            $dao,
            $docman_itemfactory
        );

        $notified_people_retriever = new NotifiedPeopleRetriever(
            $dao,
            $ugroups_to_notify_dao,
            $docman_itemfactory,
            $ugroup_manager
        );

        $users_updater   = new UsersUpdater($dao);
        $ugroups_updater = new UgroupsUpdater($ugroups_to_notify_dao);

        $docman_item = \Mockery::spy(\Docman_EmbeddedFile::class);
        $docman_itemfactory->shouldReceive('getItemFromDb')->with('b')->andReturns($docman_item);
        $docman_itemfactory->shouldReceive('getItemFromDb')->with('c')->andReturns($docman_item);
        $docman_itemfactory->shouldReceive('getItemFromDb')->with('d')->andReturns($docman_item);

        //C'est parti
        $dnmm->__construct(
            $project,
            $link_provider,
            $feedback,
            $mail_builder,
            $dao,
            $users_retriever,
            \Mockery::spy(\Tuleap\Docman\Notifications\UGroupsRetriever::class),
            $notified_people_retriever,
            $users_updater,
            $ugroups_updater
        );
        $dnmm->somethingHappen('plugin_docman_event_move', [
            'group_id' => $group_id,
            'item'    => &$d,
            'parent'  => &$c,
            'user'    => &$user,
        ]);
    }
}
