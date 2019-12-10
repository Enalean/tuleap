<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryManagerRepositoryNameTest extends TuleapTestCase
{
    private $factory;
    private $project;
    private $manager;
    private $project_id;
    private $project_name;
    private $dao;
    private $backup_directory;

    public function setUp()
    {
        parent::setUp();
        $this->project_id   = 12;
        $this->project_name = 'garden';
        $this->project      = mock('Project');
        stub($this->project)->getID()->returns($this->project_id);
        stub($this->project)->getUnixName()->returns($this->project_name);

        $this->dao                = safe_mock(GitDao::class);
        $this->backup_directory   = "/tmp/";
        $this->mirror_updater     = mock('GitRepositoryMirrorUpdater');
        $this->mirror_data_mapper = mock('Git_Mirror_MirrorDataMapper');

        $this->factory    = mock('GitRepositoryFactory');
        $this->manager    = new GitRepositoryManager(
            $this->factory,
            mock('Git_SystemEventManager'),
            $this->dao,
            $this->backup_directory,
            $this->mirror_updater,
            $this->mirror_data_mapper,
            mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
            mock('ProjectHistoryDao'),
            mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
            mock(EventManager::class)
        );
    }

    private function aRepoWithPath($path)
    {
        return aGitRepository()->withPath($this->project_name.'/'.$path.'.git')->withProject($this->project)->build();
    }

    public function itCannotCreateARepositoryWithSamePath()
    {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('bla'))
        );
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function itCannotCreateARepositoryWithSamePathThatIsNotAtRoot()
    {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('foo/bla'))
        );
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla')));
    }

    public function itForbidCreationOfRepositoriesWhenPathAlreadyExists()
    {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('bla'))
        );

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum/zaz')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla/top')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('blafoo')));
    }

    public function itForbidCreationOfRepositoriesWhenPathAlreadyExistsAndHasParents()
    {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('foo/bla'))
        );

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff/zaz')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/foo')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function itForbidCreationWhenNewRepoIsInsideExistingPath()
    {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('foo/bar/bla'))
        );

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar/zorg')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/zorg')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foobar/zorg')));
    }
}
