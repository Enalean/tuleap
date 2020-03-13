<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ManifestManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->buildMockedRepository('linux/kernel.git', 'Linux4ever');
        $this->another_repository = $this->buildMockedRepository('mozilla/firefox.git', 'free and open-source web browser');

        $this->singapour_mirror = new Git_Mirror_Mirror(\Mockery::spy(\PFUser::class), $this->singapour_mirror_id, 'singapour.io', 'singapour', 'PLP');
        $this->noida_mirror = new Git_Mirror_Mirror(\Mockery::spy(\PFUser::class), $this->noida_mirror_id, 'noida.org', 'noida', 'test');

        $this->generator   = \Mockery::spy(\Git_Mirror_ManifestFileGenerator::class);
        $this->data_mapper = \Mockery::spy(\Git_Mirror_MirrorDataMapper::class);

        $this->data_mapper->shouldReceive('fetchAll')->andReturns(array($this->singapour_mirror, $this->noida_mirror));

        $this->manager = new Git_Mirror_ManifestManager($this->data_mapper, $this->generator);
    }

    private function buildMockedRepository(string $path, string $description): GitRepository
    {
        $repositrory = Mockery::mock(GitRepository::class);
        $repositrory->shouldReceive('getPath')->andReturn($path);
        $repositrory->shouldReceive('getDescription')->andReturn($description);

        return $repositrory;
    }

    public function testItAsksToUpdateTheManifestsWhereTheRepositoryIsMirrored(): void
    {
        $this->data_mapper->shouldReceive('fetchAllRepositoryMirrors')->andReturns(array($this->noida_mirror, $this->singapour_mirror));

        $this->generator->shouldReceive('addRepositoryToManifestFile')->with(\Mockery::any(), $this->repository)->times(2);
        $this->generator->shouldReceive('addRepositoryToManifestFile')->with($this->noida_mirror, $this->repository)->ordered();
        $this->generator->shouldReceive('addRepositoryToManifestFile')->with($this->singapour_mirror, $this->repository)->ordered();

        $this->manager->triggerUpdate($this->repository);
    }

    public function testItAsksToDeleteTheRepositoryFromTheManifestsWhereTheRepositoryIsNotMirrored(): void
    {
        $this->data_mapper->shouldReceive('fetchAllRepositoryMirrors')->andReturns(array($this->noida_mirror));

        $this->generator->shouldReceive('addRepositoryToManifestFile')->with($this->noida_mirror, $this->repository)->once();
        $this->generator->shouldReceive('removeRepositoryFromManifestFile')->with($this->singapour_mirror, $this->repository->getPath())->once();

        $this->manager->triggerUpdate($this->repository);
    }

    public function testItAsksToDeleteTheRepositoryFromAllManifests(): void
    {
        $this->generator->shouldReceive('removeRepositoryFromManifestFile')->with(\Mockery::any(), $this->repository->getPath())->times(2);
        $this->generator->shouldReceive('removeRepositoryFromManifestFile')->with($this->singapour_mirror, $this->repository->getPath())->ordered();
        $this->generator->shouldReceive('removeRepositoryFromManifestFile')->with($this->noida_mirror, $this->repository->getPath())->ordered();

        $this->manager->triggerDelete($this->repository->getPath());
    }

    public function testItEnsuresThatManifestFilesOfMirrorsContainTheRepositories(): void
    {
        $this->data_mapper->shouldReceive('fetchRepositoriesForMirror')->with($this->singapour_mirror)->andReturns(array($this->repository));
        $this->data_mapper->shouldReceive('fetchRepositoriesForMirror')->with($this->noida_mirror)->andReturns(array($this->repository, $this->another_repository));

        $this->generator->shouldReceive('ensureManifestContainsLatestInfoOfRepositories')->times(2);
        $this->generator->shouldReceive('ensureManifestContainsLatestInfoOfRepositories')->with($this->singapour_mirror, array(new GitRepositoryGitoliteAdmin(), $this->repository))->ordered();
        $this->generator->shouldReceive('ensureManifestContainsLatestInfoOfRepositories')->with($this->noida_mirror, array(new GitRepositoryGitoliteAdmin(), $this->repository, $this->another_repository))->ordered();

        $this->manager->checkManifestFiles();
    }

    public function testItUpdatesTheCurrentTimeAfterAGitPush(): void
    {
        $this->data_mapper->shouldReceive('fetchAllRepositoryMirrors')->andReturns(array($this->singapour_mirror));

        $this->generator->shouldReceive('updateCurrentTimeOfRepository')->with($this->singapour_mirror, $this->repository)->once();

        $this->manager->triggerUpdateFollowingAGitPush($this->repository);
    }
}
