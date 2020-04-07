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

require_once __DIR__ . '/../../bootstrap.php';

class Git_Hook_ExtractCrossReferencesTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $git_exec_repo;
    private $user;
    private $repository;
    private $repository_in_subpath;
    private $reference_manager;
    private $post_receive;
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

        $this->user = new PFUser([
            'language_id' => 'en',
            'user_id' => 350
        ]);
        $this->reference_manager = \Mockery::spy(\ReferenceManager::class);

        $this->post_receive = new Git_Hook_ExtractCrossReferences($this->git_exec_repo, $this->reference_manager);

        $this->push_details  = new Git_Hook_PushDetails($this->repository, $this->user, 'refs/heads/master', 'whatever', 'whatever', array());
    }


    public function testItGetsEachRevisionContent(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->with('469eaa9')->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesForGivenUser(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('whatever');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any(), 350)->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnGitCommit(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('whatever');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), \Mockery::any(), Git::REFERENCE_NATURE, \Mockery::any(), \Mockery::any())->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesOnCommitMessage(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('bla bla bla');

        $this->reference_manager->shouldReceive('extractCrossRef')->with('bla bla bla', \Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function testItExtractCrossReferencesForProject(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), 101, \Mockery::any())->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function testItSetTheReferenceToTheRepository(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), 'dev/469eaa9', \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->post_receive->execute($this->push_details, '469eaa9');
    }

    public function testItSetTheReferenceToTheRepositoryWithSubRepo(): void
    {
        $this->git_exec_repo->shouldReceive('catFile')->andReturns('');

        $this->reference_manager->shouldReceive('extractCrossRef')->with(\Mockery::any(), 'arch/x86_64/dev/469eaa9', \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $push_details = new Git_Hook_PushDetails($this->repository_in_subpath, $this->user, 'refs/heads/master', 'whatever', 'whatever', array());
        $this->post_receive->execute($push_details, '469eaa9');
    }
}
