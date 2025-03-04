<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace unit\CommitMetadata;
namespace Tuleap\Git\CommitMetadata;

use Exception;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserName;

/**
 * @psalm-immutable
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorRetrieverTest extends TestCase
{
    use TemporaryTestDirectory;

    private string $git_folder;
    private \Git_Exec $git_exec;
    private \UserManager|\PHPUnit\Framework\MockObject\Stub $user_manager;

    private const COMMIT_MESSAGE = 'Lorem ipsum dolor sit amet';
    private const AUTHOR_NAME    = 'test';
    private AuthorRetriever $author_retriever;

    protected function setUp(): void
    {
        $this->git_folder = $this->getTmpDir();

        $this->git_exec = new \Git_Exec($this->git_folder);
        $this->git_exec->init();
        $this->git_exec->setLocalCommiter('test', 'test@example.com');

        $this->user_manager = $this->createStub(\UserManager::class);

        $this->author_retriever = new AuthorRetriever($this->git_exec, $this->user_manager);
    }

    private function getSha1(): string
    {
        touch("$this->git_folder/toto");
        $this->git_exec->add("$this->git_folder/toto");

        $this->git_exec->commit(self::COMMIT_MESSAGE);
        $command = new \System_Command();
        $output  = $command->exec(
            sprintf(
                '%1$s -C %2$s rev-parse HEAD',
                \Git_Exec::getGitCommand(),
                $this->git_folder
            )
        );
        if (count($output) < 1) {
            throw new Exception('Expected to find the commit we just made');
        }
        return $output[0];
    }

    public function testItReturnsTheCommitter(): void
    {
        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getUserByEmail')->willReturn($user);
        self::assertEquals(UserName::fromUser($user), $this->author_retriever->getAuthor($this->getSha1()));
    }

    public function testItFallsBackOnAuthorNameWhenItCannotMatchOnEmail(): void
    {
        $this->user_manager->method('getUserByEmail')->willReturn(null);
        self::assertEquals(UserName::fromUsername(self::AUTHOR_NAME), $this->author_retriever->getAuthor($this->getSha1()));
    }
}
