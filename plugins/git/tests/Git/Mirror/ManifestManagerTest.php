<?php
/**
 * Copyright Enalean (c) 2014 - 2015. All rights reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class Git_Mirror_ManifestManagerTest extends TuleapTestCase {

    private $manifest_directory;
    /** @var Git_Mirror_ManifestManager */
    private $manager;
    /** @var Git_Mirror_ManifestFileGenerator */
    private $generator;
    /** @var GitRepository */
    private $repository;
    /** @var GitRepository */
    private $another_repository;
    /** @var Git_Mirror_Mirror */
    private $singapour_mirror;
    private $singapour_mirror_id = 1;
    /** @var Git_Mirror_Mirror */
    private $noida_mirror;
    private $noida_mirror_id = 2;
    private $data_mapper;

    public function setUp()
    {
        parent::setUp();
        $fixture_dir              = dirname(__FILE__) .'/_fixtures';
        $this->manifest_directory = $fixture_dir .'/manifests';

        $this->repository = aGitRepository()
            ->withPath('linux/kernel.git')
            ->withDescription('Linux4ever')
            ->build();

        $this->another_repository = aGitRepository()
            ->withPath('mozilla/firefox.git')
            ->withDescription('free and open-source web browser')
            ->build();

        $this->singapour_mirror = new Git_Mirror_Mirror(mock('PFUser'), $this->singapour_mirror_id, 'singapour.io', 'singapour', 'PLP');
        $this->noida_mirror = new Git_Mirror_Mirror(mock('PFUser'), $this->noida_mirror_id, 'noida.org', 'noida', 'test');

        $this->generator   = mock('Git_Mirror_ManifestFileGenerator');
        $this->data_mapper = mock('Git_Mirror_MirrorDataMapper');

        stub($this->data_mapper)->fetchAll()->returns(array($this->singapour_mirror, $this->noida_mirror));

        $this->manager = new Git_Mirror_ManifestManager($this->data_mapper, $this->generator);
    }

    public function itAsksToUpdateTheManifestsWhereTheRepositoryIsMirrored()
    {
        stub($this->data_mapper)->fetchAllRepositoryMirrors()->returns(
            array($this->noida_mirror, $this->singapour_mirror)
        );

        expect($this->generator)->addRepositoryToManifestFile('*', $this->repository)->count(2);
        expect($this->generator)->addRepositoryToManifestFile($this->noida_mirror, $this->repository)->at(0);
        expect($this->generator)->addRepositoryToManifestFile($this->singapour_mirror, $this->repository)->at(1);

        $this->manager->triggerUpdate($this->repository);
    }

    public function itAsksToDeleteTheRepositoryFromTheManifestsWhereTheRepositoryIsNotMirrored()
    {
        stub($this->data_mapper)->fetchAllRepositoryMirrors()->returns(
            array($this->noida_mirror)
        );

        expect($this->generator)->addRepositoryToManifestFile($this->noida_mirror, $this->repository)->once();
        expect($this->generator)->removeRepositoryFromManifestFile($this->singapour_mirror, $this->repository->getPath())->once();

        $this->manager->triggerUpdate($this->repository);
    }

    public function itAsksToDeleteTheRepositoryFromAllManifests()
    {
        expect($this->generator)->removeRepositoryFromManifestFile('*', $this->repository->getPath())->count(2);
        expect($this->generator)->removeRepositoryFromManifestFile($this->singapour_mirror, $this->repository->getPath())->at(0);
        expect($this->generator)->removeRepositoryFromManifestFile($this->noida_mirror, $this->repository->getPath())->at(1);

        $this->manager->triggerDelete($this->repository->getPath());
    }

    public function itEnsuresThatManifestFilesOfMirrorsContainTheRepositories()
    {
        stub($this->data_mapper)->fetchRepositoriesForMirror($this->singapour_mirror)->returns(
            array($this->repository)
        );
        stub($this->data_mapper)->fetchRepositoriesForMirror($this->noida_mirror)->returns(
            array($this->repository, $this->another_repository)
        );

        expect($this->generator)->ensureManifestContainsLatestInfoOfRepositories()->count(2);
        expect($this->generator)
            ->ensureManifestContainsLatestInfoOfRepositories(
                $this->singapour_mirror,
                array(new GitRepositoryGitoliteAdmin(), $this->repository)
            )->at(0);
        expect($this->generator)
            ->ensureManifestContainsLatestInfoOfRepositories(
                $this->noida_mirror,
                array(new GitRepositoryGitoliteAdmin(), $this->repository, $this->another_repository)
            )->at(1);

        $this->manager->checkManifestFiles();
    }

    public function itUpdatesTheCurrentTimeAfterAGitPush()
    {
        stub($this->data_mapper)->fetchAllRepositoryMirrors()->returns(
            array($this->singapour_mirror)
        );

        expect($this->generator)->updateCurrentTimeOfRepository($this->singapour_mirror, $this->repository)->once();

        $this->manager->triggerUpdateFollowingAGitPush($this->repository);
    }
}