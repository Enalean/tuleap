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

declare(strict_types=1);

namespace Tuleap\Docman;

use Docman_Item;
use Docman_ItemFactory;
use Docman_NotificationsManager_Move;
use Docman_Path;
use Docman_PermissionsManager;
use Feedback;
use ForgeConfig;
use Generator;
use MailBuilder;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use TemplateRendererFactory;
use TestHelper;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UGroupsRetriever;
use Tuleap\Docman\Notifications\UgroupsToNotifyDao;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Docman\Notifications\UsersUpdater;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Mail\MailFilter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\TemplateRendererStub;
use UGroupManager;
use UserManager;

final class NotificationsManager_MoveTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use ForgeConfigSandbox;

    private MailFilter&MockObject $mail_filter;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_noreply', 'norelpy@example.com');
        $this->mail_filter = $this->createMock(MailFilter::class);
    }

    public static function provideTestVariations(): Generator
    {
        $bool_values     = [true, false];
        $key_from_params = static fn(bool $dr, bool $br, bool $cr, bool $lb, bool $lc, bool $ld, string $res) => "[$dr, $br, $cr, $lb, $lc, $ld, $res]";
        // {{{ Listener cannot read moved item
        // We expect no notification
        $dr = false;
        foreach ($bool_values as $br) {
            foreach ($bool_values as $cr) {
                foreach ($bool_values as $lb) {
                    foreach ($bool_values as $lc) {
                        foreach ($bool_values as $ld) {
                            yield $key_from_params($dr, $br, $cr, $lb, $lc, $ld, 'none') => [$dr, $br, $cr, $lb, $lc, $ld, 'none'];
                        }
                    }
                }
            }
        }
        //}}}
        // {{{ Listener can read moved item
        $dr = true;
        // {{{ Listener cannot read old parent
        $br = false;
        // {{{ Listener cannot read new parent
        // We expect no notification
        $cr = false;
        foreach ($bool_values as $lb) {
            foreach ($bool_values as $lc) {
                foreach ($bool_values as $ld) {
                    yield $key_from_params($dr, $br, $cr, $lb, $lc, $ld, 'none') => [$dr, $br, $cr, $lb, $lc, $ld, 'none'];
                }
            }
        }
        //}}}
        // {{{ Listener can read new parent
        // => A readable item is moved from an unreadable parent to a readable one
        $cr = true;
        // {{{ Do not listen item but maybe its parent ?
        $ld = false;
        // No listeners, no notification
        yield $key_from_params($dr, $br, $cr, false, false, $ld, 'none') => [$dr, $br, $cr, false, false, $ld, 'none'];
        // Only old parent is listened (but still unreadable), no notification
        yield $key_from_params($dr, $br, $cr, true, false, $ld, 'none') => [$dr, $br, $cr, true, false, $ld, 'none'];
        // {{{ new parent is listened, we receive a notification without b because it is still unreadable
        foreach ($bool_values as $lb) {
            yield $key_from_params($dr, $br, $cr, $lb, true, $ld, 'to_wo_b') => [$dr, $br, $cr, $lb, true, $ld, 'to_wo_b'];
        }
        //}}}
        //}}}

        //{{{ If we listen item, we receive a notification about item ("has been moved to c")
        $ld = true;
        foreach ($bool_values as $lb) {
            foreach ($bool_values as $lc) {
                yield $key_from_params($dr, $br, $cr, $lb, $lc, $ld, 'item') => [$dr, $br, $cr, $lb, $lc, $ld, 'item'];
            }
        }
        //}}}
        //}}}
        //}}}
        // {{{ Listener can read old parent
        $br = true;
        // {{{ Listener cannot read new parent
        // We have to send notifications only when old parent or item is listened
        $cr = false;
        foreach ($bool_values as $lb) {
            foreach ($bool_values as $lc) {
                foreach ($bool_values as $ld) {
                    yield $key_from_params($dr, $br, $cr, $lb, $lc, $ld, $lb || $ld ? 'from_wo_c' : 'none') => [$dr, $br, $cr, $lb, $lc, $ld, $lb || $ld ? 'from_wo_c' : 'none'];
                }
            }
        }
        //}}}
        // {{{ Listener can read new parent
        $cr = true;
        // {{{ Moved item is listened, notification on item
        $ld = true;
        foreach ($bool_values as $lb) {
            foreach ($bool_values as $lc) {
                yield $key_from_params($dr, $br, $cr, $lb, $lc, $ld, 'item') => [$dr, $br, $cr, $lb, $lc, $ld, 'item'];
            }
        }
        //}}}
        //{{{ Moved item is not listened
        $ld = false;
        // {{{ new parent is listened, notification 'to'
        $lc = true;
        foreach ($bool_values as $lb) {
            yield $key_from_params($dr, $br, $cr, $lb, $lc, $ld, 'to') => [$dr, $br, $cr, $lb, $lc, $ld, 'to'];
        }
        //}}}
        // {{{ new parent is not listened
        $lc = false;
        //Old parent is listened, 'from' notification
        yield $key_from_params($dr, $br, $cr, true, $lc, $ld, 'from') => [$dr, $br, $cr, true, $lc, $ld, 'from'];
        //No listener, no notification
        yield $key_from_params($dr, $br, $cr, false, $lc, $ld, 'none') => [$dr, $br, $cr, false, $lc, $ld, 'none'];
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
     * @param $dr bool d is readable
     * @param $br bool b is readable
     * @param $cr bool c is readable
     * @param $lb bool b is listened
     * @param $lc bool c is listened
     * @param $ld bool d is listened
     * @param $res 'item'|'from'|'from_wo_c'|'to'|'to_wo_b'|'none' expected result
     * @dataProvider provideTestVariations
     */
    public function testNotification(bool $dr, bool $br, bool $cr, bool $lb, bool $lc, bool $ld, string $res): void
    {
        $a = new Docman_Item(['item_id' => 102, 'parent_id' => 0]);
        $b = new Docman_Item(['item_id' => 103, 'parent_id' => 102]);
        $c = new Docman_Item(['item_id' => 104, 'parent_id' => 102]);
        $d = new Docman_Item(['item_id' => 105, 'parent_id' => 103]);

        $group_id = 101;
        $project  = ProjectTestBuilder::aProject()->withId($group_id)->withAccessPrivate()->build();

        $user     = UserTestBuilder::buildWithId(101);
        $listener = UserTestBuilder::buildWithId(102);

        $feedback = new Feedback();

        $item_factory = $this->createMock(Docman_ItemFactory::class);
        $item_factory->method('getItemFromDb')->willReturnMap([
            $a->getId() => [$a],
            $b->getId() => [$b],
            $c->getId() => [$c],
            $d->getId() => [$d],
        ]);

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->willReturnMap([
            $user->getId()     => [$user],
            $listener->getId() => [$listener],
        ]);

        $permissions_manager = $this->createMock(Docman_PermissionsManager::class);
        $permissions_manager->method('userCanRead')->with($listener, self::anything())
            ->willReturnCallback(static fn(PFUser $user, $item_id) => match ($item_id) {
                $a->getId() => true,
                $c->getId() => $cr,
                $b->getId() => $br,
                $d->getId() => $dr,
            });
        $permissions_manager->method('userCanAccess')->with($listener, self::anything())
            ->willReturnCallback(static fn(PFUser $user, $item_id) => match ($item_id) {
                $a->getId() => true,
                $c->getId() => $cr,
                $b->getId() => $br,
                $d->getId() => $dr && $br,
            });

        $dao = $this->createMock(UsersToNotifyDao::class);
        $dao->method('searchUserIdByObjectIdAndType')
            ->willReturnCallback(static fn($item_id) => match ($item_id) {
                $d->getId() => $ld ? TestHelper::arrayToDar(['user_id' => $listener->getId(), 'item_id' => $d->getId()]) : TestHelper::emptyDar(),
                $c->getId() => $ld ? TestHelper::arrayToDar(['user_id' => $listener->getId(), 'item_id' => $c->getId()]) : TestHelper::emptyDar(),
                $b->getId() => $lb ? TestHelper::arrayToDar(['user_id' => $listener->getId(), 'item_id' => $b->getId()]) : TestHelper::emptyDar(),
            });

        $docman_path = new Docman_Path();

        $dnmm = $this->createPartialMock(Docman_NotificationsManager_Move::class, [
            '_getItemFactory',
            '_getUserManager',
            '_getPermissionsManager',
            '_getDocmanPath',
            '_buildMessage',
        ]);
        $dnmm->method('_getItemFactory')->willReturn($item_factory);
        $dnmm->method('_getUserManager')->willReturn($user_manager);
        $dnmm->method('_getPermissionsManager')->willReturn($permissions_manager);
        $dnmm->method('_getDocmanPath')->willReturn($docman_path);

        if ($res === 'none') {
            $dnmm->expects(self::never())->method('_buildMessage');
        } else {
            self::expectNotToPerformAssertions();
        }

        $ugroups_to_notify_dao = $this->createMock(UgroupsToNotifyDao::class);
        $ugroups_to_notify_dao->method('searchUgroupsByItemIdAndType')->willReturn(false);
        $ugroup_manager          = $this->createMock(UGroupManager::class);
        $template_render_factory = $this->createMock(TemplateRendererFactory::class);
        $template_rendder        = new TemplateRendererStub();
        $template_render_factory->method('getRenderer')->willReturn($template_rendder);
        $link_provider = new DocumentLinkProvider('', $project);

        $mail_builder    = new MailBuilder($template_render_factory, $this->mail_filter);
        $users_retriever = new UsersRetriever($dao, $item_factory);

        $notified_people_retriever = new NotifiedPeopleRetriever(
            $dao,
            $ugroups_to_notify_dao,
            $item_factory,
            $ugroup_manager
        );

        $users_updater   = new UsersUpdater($dao);
        $ugroups_updater = new UgroupsUpdater($ugroups_to_notify_dao);

        // Let's go
        $dnmm->__construct(
            $project,
            $link_provider,
            $feedback,
            $mail_builder,
            $dao,
            $users_retriever,
            $this->createMock(UGroupsRetriever::class),
            $notified_people_retriever,
            $users_updater,
            $ugroups_updater
        );
        $dnmm->somethingHappen('plugin_docman_event_move', [
            'group_id' => $group_id,
            'item'     => &$d,
            'parent'   => &$c,
            'user'     => &$user,
        ]);
    }
}
