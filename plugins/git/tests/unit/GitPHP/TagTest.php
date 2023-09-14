<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitPHP;

use Git_Exec;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;

final class TagTest extends TestCase
{
    use TemporaryTestDirectory;

    private string $fixture_dir;
    private Git_Exec $git_exec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture_dir = $this->getTmpDir() . '/tuleap-gitphp-tag-test_' . random_int(0, 99999999);
        mkdir($this->fixture_dir);

        $this->git_exec = new Git_Exec($this->fixture_dir);
        $this->git_exec->init();
        $this->git_exec->setLocalCommiter('test', 'test@example.com');
    }

    protected function tearDown(): void
    {
        system("rm -rf $this->fixture_dir");

        parent::tearDown();
    }

    public function testItReturnsCommitSHA1ofTag(): void
    {
        $tag_content = <<<EOF
object 27f1d88dc86a73284d6c85448ec46de2cdb5bb95
type commit
tag v5
tagger Toto <toto@example.com> 1643295365 +0100

Version 5
EOF;

        $project = $this->createMock(Project::class);
        $project->method('GetObject')->with('6ab479decb315611faa0cc6252816109884636b6', 0)->willReturn($tag_content);
        $project->method('GetCommit')->with('27f1d88dc86a73284d6c85448ec46de2cdb5bb95')->willReturn(
            new Commit($project, '27f1d88dc86a73284d6c85448ec46de2cdb5bb95')
        );

        $tag = new Tag($project, 'v5', '6ab479decb315611faa0cc6252816109884636b6');

        self::assertSame(
            '27f1d88dc86a73284d6c85448ec46de2cdb5bb95',
            $tag->GetCommit()->GetHash()
        );
    }

    public function testItReturnsCommitSHA1ofNestedTags(): void
    {
        $project = new Project($this->fixture_dir, '.git', $this->git_exec);
        $gitexe  = new GitExe($project);

        //add initial commit
        touch("$this->fixture_dir/stuff");
        $this->git_exec->add("$this->fixture_dir/stuff");
        $this->git_exec->commit("add stuff file");

        //get commit SHA1
        $initial_commit_sha1 = trim($gitexe->Execute('rev-parse', ['HEAD']));

        //add tags
        $gitexe->Execute('tag', ['v1']);
        $gitexe->Execute('checkout', ['v1', '-q']);
        $gitexe->Execute('tag', ['v1nested']);

        $nested_tag = new Tag($project, 'v1nested', '');
        self::assertSame(
            $initial_commit_sha1,
            $nested_tag->GetCommit()->GetHash(),
        );
    }
}
