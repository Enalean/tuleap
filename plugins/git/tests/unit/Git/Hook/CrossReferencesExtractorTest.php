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
use Git_Hook_PushDetails;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;

require_once __DIR__ . '/../../bootstrap.php';

class CrossReferencesExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CrossReferencesExtractor
     */
    private $post_receive;
    /**
     * @var \Git_Exec|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $git_exec_repo;
    /**
     * @var \GitRepository|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $repository;
    /**
     * @var \GitRepository|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $repository_in_subpath;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Git_Hook_PushDetails
     */
    private $push_details;

    protected function setUp(): void
    {
        parent::setUp();

        $project = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns(101)->getMock();

        $this->git_exec_repo = \Mockery::spy(\Git_Exec::class);

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getFullName')->andReturns('dev');
        $this->repository->shouldReceive('getProject')->andReturns($project);

        $this->repository_in_subpath = \Mockery::spy(\GitRepository::class);
        $this->repository_in_subpath->shouldReceive('getProject')->andReturns($project);
        $this->repository_in_subpath->shouldReceive('getFullName')->andReturns('arch/x86_64/dev');

        $this->user              = new PFUser([
            'language_id' => 'en',
            'user_id' => 350,
        ]);
        $this->reference_manager = \Mockery::spy(\ReferenceManager::class);

        $this->post_receive = new CrossReferencesExtractor($this->git_exec_repo, $this->reference_manager);

        $this->push_details = new Git_Hook_PushDetails($this->repository, $this->user, 'refs/heads/master', 'whatever', 'whatever', []);
    }

    public function testItGetsEachRevisionContent(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->with('469eaa9')->once();

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesForGivenUser(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('whatever');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any(), 350)->once();

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnGitCommit(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('whatever');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), \Mockery::any(), Git::REFERENCE_NATURE, \Mockery::any(), \Mockery::any())->once();

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnCommitMessage(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('bla bla bla');

        $this->reference_manager->shouldReceive('extractCrossRef')->with('bla bla bla', \Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesForProject(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), 101, \Mockery::any())->once();

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItSetTheReferenceToTheRepository(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), 'dev/469eaa9', \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->post_receive->extractCommitReference($this->push_details, '469eaa9');
    }

    public function testItSetTheReferenceToTheRepositoryWithSubRepo(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), 'arch/x86_64/dev/469eaa9', \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $push_details = new Git_Hook_PushDetails($this->repository_in_subpath, $this->user, 'refs/heads/master', 'whatever', 'whatever', []);
        $this->post_receive->extractCommitReference($push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnGitTag(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('This tags art #123');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(
            'This tags art #123',
            'dev/v1',
            Git::TAG_REFERENCE_NATURE,
            101,
            350
        )
            ->once();

        $tag_push_details = new Git_Hook_PushDetails(
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
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('This tags art #123');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(
            'This tags art #123',
            'dev/v1',
            Git::TAG_REFERENCE_NATURE,
            101,
            350
        )
            ->once();

        $tag_push_details = new Git_Hook_PushDetails(
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
