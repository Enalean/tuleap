<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\Git;

use Git;
use GitRepositoryFactory;
use SystemEventDao;
use SystemEventManager;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitTest extends TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    #[\Override]
    protected function setup(): void
    {
        $system_event_manager = $this->createStub(SystemEventManager::class);
        $sys_dao              = $this->createStub(SystemEventDao::class);
        $sys_dao->method('searchWithParam')->willReturn([]);
        $system_event_manager->method('_getDao')->willReturn($sys_dao);

        SystemEventManager::setInstance($system_event_manager);
    }

    #[\Override]
    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();
        unset($_SERVER['REQUEST_METHOD'], $GLOBALS['_SESSION']);
    }

    public function testTheDelRouteExecutesDeleteRepositoryWithTheIndexView(): void
    {
        $GLOBALS['Language']->method('gettext')->willReturn('Something');
        $usermanager = $this->createStub(UserManager::class);
        $usermanager->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request                   = new \Tuleap\HTTPRequest(['repo_id' => 1]);

        $git = $this->createPartialMock(Git::class, ['addAction', 'definePermittedActions', 'addView', 'checkSynchronizerToken']);
        $git->setRequest($request);
        $git->setUserManager($usermanager);
        $git->setAction('del');
        $git->setPermittedActions(['del']);

        $repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build();

        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->method('getRepositoryById')->willReturn($repository);
        $git->setFactory($factory);

        $git->method('checkSynchronizerToken');
        $git->expects($this->once())->method('addAction')->with('deleteRepository', self::anything());
        $git->expects($this->once())->method('definePermittedActions');
        $git->expects($this->once())->method('addView')->with('index');

        $git->request();
    }
}
