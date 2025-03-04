<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Git\Hook;

use Git;
use Git_Exec;
use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ReferenceManager;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferencesExtractorTest extends TestCase
{
    private CrossReferencesExtractor $post_receive;
    private Git_Exec&MockObject $git_exec_repo;
    private GitRepository $repository;
    private GitRepository $repository_in_subpath;
    private PFUser $user;
    private ReferenceManager&MockObject $reference_manager;
    private PushDetails $push_details;

    protected function setUp(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->git_exec_repo = $this->createMock(Git_Exec::class);

        $this->repository            = GitRepositoryTestBuilder::aProjectRepository()->withName('dev')->inProject($project)->build();
        $this->repository_in_subpath = GitRepositoryTestBuilder::aProjectRepository()->withName('arch/x86_64/dev')->inProject($project)->build();

        $this->user              = new PFUser([
            'language_id' => 'en',
            'user_id'     => 350,
        ]);
        $this->reference_manager = $this->createMock(ReferenceManager::class);

        $this->post_receive = new CrossReferencesExtractor($this->git_exec_repo, $this->reference_manager);

        $this->push_details = new PushDetails($this->repository, $this->user, 'refs/heads/master', 'whatever', 'whatever', []);
    }

    public function testItGetsEachRevisionContent(): void
    {
        $this->git_exec_repo->expects(self::once())->method('catFile')->with('469eaa9');
        $this->reference_manager->method('extractCrossRef');

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesForGivenUser(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('whatever');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(self::anything(), self::anything(), self::anything(), self::anything(), 350);

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnGitCommit(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('whatever');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(self::anything(), self::anything(), Git::REFERENCE_NATURE, self::anything(), self::anything());

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnCommitMessage(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('bla bla bla');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with('bla bla bla', self::anything(), self::anything(), self::anything(), self::anything());

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesForProject(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(self::anything(), self::anything(), self::anything(), 101, self::anything());

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItSetTheReferenceToTheRepository(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(self::anything(), 'dev/469eaa9', self::anything(), self::anything(), self::anything());

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItSetTheReferenceToTheRepositoryWithSubRepo(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(self::anything(), 'arch/x86_64/dev/469eaa9', self::anything(), self::anything(), self::anything());

        $push_details = new PushDetails($this->repository_in_subpath, $this->user, 'refs/heads/master', 'whatever', 'whatever', []);
        $this->post_receive->extractCommitReference($push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnGitTag(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('This tags art #123');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(
            'This tags art #123',
            'dev/v1',
            Git::TAG_REFERENCE_NATURE,
            101,
            350
        );

        $tag_push_details = new PushDetails(
            $this->repository,
            $this->user,
            'refs/tags/v1',
            'create',
            'tag',
            []
        );

        $this->post_receive->extractTagReference($tag_push_details);
    }

    public function testItExtractCrossReferencesOnGitTagWithoutFullReference(): void
    {
        $this->git_exec_repo->method('catFile')->willReturn('This tags art #123');

        $this->reference_manager->expects(self::once())->method('extractCrossRef')->with(
            'This tags art #123',
            'dev/v1',
            Git::TAG_REFERENCE_NATURE,
            101,
            350
        );

        $tag_push_details = new PushDetails(
            $this->repository,
            $this->user,
            'v1',
            'create',
            'tag',
            []
        );

        $this->post_receive->extractTagReference($tag_push_details);
    }
}
