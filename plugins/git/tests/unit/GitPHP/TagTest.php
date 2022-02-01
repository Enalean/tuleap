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

use Tuleap\Test\PHPUnit\TestCase;

final class TagTest extends TestCase
{
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
        $nested_tag_content = <<<EOF
object 6ab479decb315611faa0cc6252816109884636b6
type tag
tag v5.3
tagger Toto <toto@example.com> 1643295518 +0100

create tag 5.3
EOF;

        $tag_content = <<<EOF
object 27f1d88dc86a73284d6c85448ec46de2cdb5bb95
type commit
tag v5
tagger Toto <toto@example.com> 1643295365 +0100

Version 5
EOF;

        $project = $this->createMock(Project::class);
        $project->method('GetObject')->willReturnMap(
            [
                ['353bc8c7b54ef3beb976e2faca2f3c0be974eeff', 0, $nested_tag_content],
                ['6ab479decb315611faa0cc6252816109884636b6', 0, $tag_content],
            ],
        );
        $project->method('GetCommit')->with('v5')->willReturn(
            new Commit($project, '27f1d88dc86a73284d6c85448ec46de2cdb5bb95')
        );
        $project->method('GetTag')->with('v5')->willReturn(
            new Tag($project, 'v5', '6ab479decb315611faa0cc6252816109884636b6')
        );

        $nested_tag = new Tag($project, 'v5.3', '353bc8c7b54ef3beb976e2faca2f3c0be974eeff');

        self::assertSame(
            '27f1d88dc86a73284d6c85448ec46de2cdb5bb95',
            $nested_tag->GetCommit()->GetHash()
        );
    }
}
