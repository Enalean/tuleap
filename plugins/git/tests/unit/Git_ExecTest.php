<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git;

use Exception;
use Git_Command_Exception;
use Git_Exec;
use System_Command;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Git_ExecTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    use TemporaryTestDirectory;

    private const string NULL_SHA1 = '0000000000000000000000000000000000000000';

    private string $fixture_dir;
    private Git_Exec $git_exec;
    private string $symlink_repo;

    #[\Override]
    protected function setUp(): void
    {
        $this->symlink_repo = $this->getTmpDir() . '/tuleap-git-exec-test_' . random_int(0, 99999999);
        $this->fixture_dir  = $this->getTmpDir() . '/tuleap-git-exec-test_' . random_int(0, 99999999);
        mkdir($this->fixture_dir);
        symlink($this->fixture_dir, $this->symlink_repo);

        $this->git_exec = new Git_Exec($this->fixture_dir);
        $this->git_exec->init();
        $this->git_exec->setLocalCommiter('test', 'test@example.com');
    }

    #[\Override]
    protected function tearDown(): void
    {
        system("rm -rf $this->fixture_dir");
        unlink($this->symlink_repo);
    }

    public function testThereIsSomethingToCommitWhenStuffIsAdded(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        self::assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsSomethingToCommitWhenStuffIsRemoved(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit('add stuff');
        $this->git_exec->rm("$this->fixture_dir/toto");
        self::assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsSomethingToCommitWhenStuffIsMoved(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit('add stuff');
        $this->git_exec->mv("$this->fixture_dir/toto", "$this->fixture_dir/tata");
        self::assertTrue($this->git_exec->isThereAnythingToCommit());
    }

    public function testItMovesTheFilesInSymlinkedRepo(): void
    {
        $file_orig_path = $this->symlink_repo . '/file1';
        $file_dest_path = $this->symlink_repo . '/file with spaces';
        file_put_contents($file_orig_path, 'bla');
        $git_exec = new Git_Exec($this->symlink_repo);
        $git_exec->add($file_orig_path);
        $git_exec->commit('bla');
        $git_exec->mv($file_orig_path, $file_dest_path);
        $git_exec->commit('rename');
        self::assertFalse($git_exec->isThereAnythingToCommit());
        self::assertFalse(file_exists($file_orig_path));
        self::assertEquals('bla', file_get_contents($file_dest_path));
    }

    public function testThereIsNothingToCommitOnEmptyRepository(): void
    {
        self::assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testGetAllBranchesSortedByCreationDate(): void
    {
        $file_orig_path = $this->symlink_repo . '/test';
        file_put_contents($file_orig_path, 'test');
        $this->git_exec->add($file_orig_path);
        try {
            putenv('GIT_AUTHOR_DATE=2000-01-01T00:00:01');
            putenv('GIT_COMMITTER_DATE=2000-01-01T00:00:01');
            $this->git_exec->commit('test');
        } finally {
            putenv('GIT_AUTHOR_DATE');
            putenv('GIT_COMMITTER_DATE');
        }
        $git_cmd = Git_Exec::getGitCommand();
        system("cd $this->fixture_dir && $git_cmd checkout -b test --quiet");
        file_put_contents($file_orig_path, 'test2');
        $this->git_exec->add($file_orig_path);
        $this->git_exec->commit('test2');

        self::assertEquals(['test', 'main'], $this->git_exec->getAllBranchesSortedByCreationDate());
    }

    public function testGetAllTagsSortedByCreationDate(): void
    {
        $file_orig_path = $this->symlink_repo . '/test';
        file_put_contents($file_orig_path, 'test');
        $this->git_exec->add($file_orig_path);
        $this->git_exec->commit('test');
        system("cd $this->fixture_dir && " . Git_Exec::getGitCommand() . ' tag -a test -m oui');

        self::assertEquals(['test'], $this->git_exec->getAllTagsSortedByCreationDate());
    }

    public function testThereIsNothingToCommitOnAlreadyCommitedRepo(): void
    {
        touch("$this->fixture_dir/toto");
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit('add stuff');
        self::assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitWhenNewFilesAreNotAdded(): void
    {
        touch("$this->fixture_dir/toto");
        self::assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testThereIsNothingToCommitWhenContentDoesntChange(): void
    {
        file_put_contents("$this->fixture_dir/toto", 'stuff');
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit('add stuff');
        file_put_contents("$this->fixture_dir/toto", 'stuff');
        self::assertFalse($this->git_exec->isThereAnythingToCommit());
    }

    public function testItDoesntRaiseAnErrorWhenTryingToRemoveAnUntrackedFile(): void
    {
        file_put_contents("$this->fixture_dir/toto", 'stuff');
        $this->git_exec->rm("$this->fixture_dir/toto");

        //All is OK
        self::assertTrue(true);
    }

    public function testItReturnsTrueWhenTheRevExists(): void
    {
        file_put_contents("$this->fixture_dir/toto", 'stuff');
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit('add stuff');

        $res = $this->git_exec->doesObjectExists('main');

        self::assertTrue($res);
    }

    public function testItReturnsFalseWhenTheRevDoesNotExist(): void
    {
        $res = $this->git_exec->doesObjectExists('this_is_not_a_rev');

        self::assertFalse($res);
    }

    public function testItReturnsEachLineOfTheCommitMessage(): void
    {
        $message     = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod\n"
                       . "\n"
                       . "tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,\n"
                       . "quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo\n"
                       . "consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse\n"
                       . "cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non\n"
                       . 'proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
        $commit_sha1 = $this->getSha1($message);

        $result = $this->git_exec->getCommitMessage($commit_sha1);

        self::assertSame($message, $result);
    }

    public function testItThrowsWhenTheReferenceDoesNotExist(): void
    {
        $this->expectException(Git_Command_Exception::class);
        $this->git_exec->getCommitMessage(self::NULL_SHA1);
    }

    public function testRetrievesDefaultBranch(): void
    {
        self::assertEquals('main', $this->git_exec->getDefaultBranch());
    }

    public function testSetDefaultBranch(): void
    {
        $this->git_exec->setDefaultBranch('foo');
        self::assertEquals('foo', $this->git_exec->getDefaultBranch());
    }

    public function testUpdateRef(): void
    {
        //we must add content
        file_put_contents("$this->fixture_dir/toto", 'stuff');
        $this->git_exec->add("$this->fixture_dir/toto");
        $this->git_exec->commit('add stuff');

        $this->git_exec->updateRef('refs/heads/new_branch', 'main');
        self::assertEqualsCanonicalizing(['main', 'new_branch'], $this->git_exec->getAllBranchesSortedByCreationDate());
    }

    public function testRetrieveAuthorEmail(): void
    {
        $author = 'test@example.com';
        $this->git_exec->setLocalCommiter('test', $author);
        $commit_sha1 = $this->getSha1('add stuff');

        self::assertSame($author, $this->git_exec->getAuthorInformation($commit_sha1)['email']);
    }

    public function testRetrieveAuthorName(): void
    {
        $author = 'test';
        $this->git_exec->setLocalCommiter($author, 'test@example.com');
        $commit_sha1 = $this->getSha1('add stuff');

        self::assertSame($author, $this->git_exec->getAuthorInformation($commit_sha1)['name']);
    }

    private function getSha1(string $commit_message): string
    {
        touch("$this->fixture_dir/toto");
        //we must add content
        file_put_contents("$this->fixture_dir/toto", 'stuff');
        $this->git_exec->add("$this->fixture_dir/toto");

        $this->git_exec->commit($commit_message);
        $command = new System_Command();
        $output  = $command->exec(
            sprintf(
                '%1$s -C %2$s rev-parse HEAD',
                Git_Exec::getGitCommand(),
                $this->fixture_dir
            )
        );
        if (count($output) < 1) {
            throw new Exception('Expected to find the commit we just made');
        }
        return $output[0];
    }
}
