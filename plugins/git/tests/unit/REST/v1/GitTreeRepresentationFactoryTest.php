<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

use GitRepositoryException;
use Tuleap\Git\GitPHP\Blob;
use Tuleap\Git\GitPHP\BlobDataReader;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Pack;
use Tuleap\Git\GitPHP\Project;
use Tuleap\Git\GitPHP\Tree;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitTreeRepresentationFactoryTest extends TestCase
{
    private GitTreeRepresentationFactory $git_tree_representation_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->git_tree_representation_factory = new GitTreeRepresentationFactory();
    }

    public function testItThrowsExceptionIfTheGivenReferenceDoesNotExists(): void
    {
        $ref = 'this_is_not_a_ref';

        $git_repository = $this->createMock(Project::class);
        $git_repository->method('GetCommit')->with($ref)->willReturn(null);

        $this->expectException(GitRepositoryException::class);

        $this->git_tree_representation_factory->getGitTreeRepresentation('any_path', $ref, $git_repository);
    }

    public function testItThrowsAnExceptionIfTheGivenPathPointsToAFile(): void
    {
        $ref  = 'whatever_ref';
        $hash = 'whatever_hash';
        $path = 'whatever_path';

        $commit = $this->createMock(Commit::class);
        $commit->method('PathToHash')->with($path)->willReturn($hash);

        $git_repository = new class ($commit) extends Project {
            private Commit $commit;

            public function __construct(Commit $commit)
            {
                $this->commit = $commit;
            }

            #[\Override]
            public function GetCommit($hash): Commit // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                return $this->commit;
            }

            #[\Override]
            public function GetObject($hash, &$type = 0): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                $type = Pack::OBJ_BLOB;
            }
        };

        $this->expectException(GitRepositoryException::class);
        $this->git_tree_representation_factory->getGitTreeRepresentation($path, $ref, $git_repository);
    }

    public function testItThrowsAnExceptionIfTheGivenPathPointsToNoWhere(): void
    {
        $ref  = 'whatever_ref';
        $hash = 'whatever_hash';
        $path = 'whatever_path';

        $commit = $this->createMock(Commit::class);
        $commit->method('PathToHash')->with($path)->willReturn($hash);

        $git_repository = new class ($commit) extends Project {
            private Commit $commit;

            public function __construct(Commit $commit)
            {
                $this->commit = $commit;
            }

            #[\Override]
            public function GetCommit($hash): Commit // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                return $this->commit;
            }

            #[\Override]
            public function GetObject($hash, &$type = 0): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
            }
        };

        $this->expectException(GitRepositoryException::class);
        $this->git_tree_representation_factory->getGitTreeRepresentation($path, $ref, $git_repository);
    }

    public function testItReturnsACollectionOfGitTreeRepresentations(): void
    {
        $ref  = 'whatever_ref';
        $hash = 'whatever_hash';
        $path = 'whatever_path';

        $commit = $this->createMock(Commit::class);
        $commit->method('PathToHash')->with($path)->willReturn($hash);

        $dir_hash  = '1509a777f2e76bcfa151947721c8989ec13747c0';
        $file_hash = '7b9e33f88c8e79eeebc819e6ea3bc6e6663e734f';

        $main_tree = $this->createMock(Tree::class);

        $git_repository = new class ($commit, $dir_hash, $file_hash, $main_tree) extends Project {
            private Commit $commit;
            private string $dir_hash;
            private string $file_hash;
            private Tree $main_tree;

            public function __construct(Commit $commit, string $dir_hash, string $file_hash, Tree $main_tree)
            {
                $this->commit    = $commit;
                $this->dir_hash  = $dir_hash;
                $this->file_hash = $file_hash;
                $this->main_tree = $main_tree;
            }

            #[\Override]
            public function GetCommit($hash): Commit // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                return $this->commit;
            }

            #[\Override]
            public function GetObject($hash, &$type = 0): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                switch ($hash) {
                    case $this->dir_hash:
                        $type = Pack::OBJ_TREE;
                        break;
                    case $this->file_hash:
                        $type = Pack::OBJ_BLOB;
                        break;
                    default:
                        $type = Pack::OBJ_TREE;
                }
            }

            #[\Override]
            public function GetTree($hash): Tree // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                return $this->main_tree;
            }
        };


        $file1 = new Blob(new BlobDataReader(), $git_repository, $file_hash);
        $file1->SetPath('file1');
        $file1->SetMode('100644');

        $file2 = new Blob(new BlobDataReader(), $git_repository, $file_hash);
        $file2->SetPath('file2');
        $file2->SetMode('120000');

        $directory1 = new Tree($git_repository, $dir_hash);
        $directory1->SetPath('directory1');
        $directory1->SetMode('040000');


        $main_directory_contents = [$file1, $directory1, $file2];
        $main_tree->method('GetContents')->willReturn($main_directory_contents);

        $expected_representation = [
            new GitTreeRepresentation($file1->GetMode(), 'blob', 'file1', 'whatever_path/file1', $file_hash),
            new GitTreeRepresentation($directory1->GetMode(), 'tree', 'directory1', 'whatever_path/directory1', $dir_hash),
            new GitTreeRepresentation($file2->GetMode(), 'blob', 'file2', 'whatever_path/file2', $file_hash),
        ];

        $representation = $this->git_tree_representation_factory->getGitTreeRepresentation($path, $ref, $git_repository);

        self::assertEquals($expected_representation, $representation);
    }
}
