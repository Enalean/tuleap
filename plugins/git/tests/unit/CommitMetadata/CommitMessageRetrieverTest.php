<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\CommitMetadata;

use Tuleap\TemporaryTestDirectory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitMessageRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private string $git_folder;
    private \Git_Exec $git_exec;

    private const string COMMIT_MESSAGE = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod\n"
    . "\n"
    . "tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,\n"
    . "quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo\n"
    . "consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse\n"
    . "cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non\n"
    . 'proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

    #[\Override]
    protected function setUp(): void
    {
        $this->git_folder = $this->getTmpDir();

        $this->git_exec = new \Git_Exec($this->git_folder);
        $this->git_exec->init();
        $this->git_exec->setLocalCommiter('test', 'test@example.com');
    }

    private function getCommitMessage(): string
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
        $commit_sha1 = $output[0];

        $retriever = new CommitMessageRetriever($this->git_exec);
        return $retriever->getCommitMessage($commit_sha1);
    }

    public function testItReturnsTheCommitMessageJoinedWithNewlines(): void
    {
        self::assertSame(self::COMMIT_MESSAGE, $this->getCommitMessage());
    }
}
