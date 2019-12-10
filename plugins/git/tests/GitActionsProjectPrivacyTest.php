<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/builders/aGitRepository.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitActionsProjectPrivacyTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->dao = \Mockery::spy(\GitDao::class);
        $this->factory = \Mockery::spy(\GitRepositoryFactory::class);
    }

    public function itDoesNothingWhenThereAreNoRepositories()
    {
        $project_id = 99;
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array());
        $this->changeProjectRepositoriesAccess($project_id, true);
        $this->changeProjectRepositoriesAccess($project_id, false);
    }

    public function itDoesNothingWeAreMakingItTheProjectPublic()
    {
        $project_id = 99;
        $is_private = false;
        $repo_id = 333;
        $repo = mockery_stub(\GitRepository::class)->setAccess()->never()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function itMakesRepositoriesPrivateWhenProjectBecomesPrivate()
    {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = mockery_stub(\GitRepository::class)->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function itDoesNothingIfThePermissionsAreAlreadyCorrect()
    {
        $project_id = 99;
        $is_private = true;
        $repo_id = 333;
        $repo = mockery_stub(\GitRepository::class)->setAccess()->never()->returns("whatever");
        stub($repo)->getAccess()->returns(GitRepository::PRIVATE_ACCESS);
        stub($repo)->changeAccess()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id => null));
        stub($this->factory)->getRepositoryById($repo_id)->returns($repo);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    public function itHandlesAllRepositoriesOfTheProject()
    {
        $project_id = 99;
        $is_private = true;
        $repo_id1 = 333;
        $repo_id2 = 444;
        $repo1 = mockery_stub(\GitRepository::class)->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        $repo2 = mockery_stub(\GitRepository::class)->setAccess(GitRepository::PRIVATE_ACCESS)->once()->returns("whatever");
        stub($this->dao)->getProjectRepositoryList($project_id)->returns(array($repo_id1 => null, $repo_id2 => null));
        stub($this->factory)->getRepositoryById($repo_id1)->returns($repo1);
        stub($this->factory)->getRepositoryById($repo_id2)->returns($repo2);
        $this->changeProjectRepositoriesAccess($project_id, $is_private);
    }

    private function changeProjectRepositoriesAccess($project_id, $is_private)
    {
        return GitActions::changeProjectRepositoriesAccess($project_id, $is_private, $this->dao, $this->factory);
    }
}
