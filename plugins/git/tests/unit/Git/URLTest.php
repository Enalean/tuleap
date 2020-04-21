<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_URL_GitSmartHTTPTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ProjectManager **/
    protected $project_manager;

    /** @var GitRepositoryFactory **/
    protected $repository_factory;

    /** @var Project */
    protected $gpig_project;

    /** @var GitRepository */
    protected $goldfish_repository;

    /** @var GitRepository */
    protected $apache_repository;

    protected $gpig_project_name = 'gpig';
    protected $gpig_project_id   = '111';
    protected $repository_id     = '43';

    protected function setUp(): void
    {
        parent::setUp();
        $this->project_manager     = \Mockery::spy(\ProjectManager::class);
        $this->repository_factory  = \Mockery::spy(\GitRepositoryFactory::class);
        $this->gpig_project        = \Mockery::spy(\Project::class);
        $this->gpig_project->shouldReceive('getId')->andReturns($this->gpig_project_id);
        $this->gpig_project->shouldReceive('getUnixName')->andReturns($this->gpig_project_name);

        $this->goldfish_repository = $this->buildRepository($this->gpig_project, 'device/generic/goldfish');

        $this->repository_factory->shouldReceive('getByProjectNameAndPath')->with($this->gpig_project_name, 'device/generic/goldfish.git')->andReturns($this->goldfish_repository);

        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->repository_id)->andReturns($this->goldfish_repository);

        $this->apache_repository = $this->buildRepository($this->gpig_project, 'apache-2.5');

        $this->repository_factory->shouldReceive('getByProjectNameAndPath')->with($this->gpig_project_name, 'apache-2.5.git')->andReturns($this->apache_repository);

        $this->project_manager->shouldReceive('getProject')->with($this->gpig_project_id)->andReturns($this->gpig_project);
    }

    private function buildRepository(Project $project, string $name): GitRepository
    {
        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturn($project);
        $repository->shouldReceive('getName')->andReturn($name);

        return $repository;
    }

    public function testItRetrievesTheRepository(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');

        $this->assertEquals($this->goldfish_repository, $url->getRepository());
    }

    public function testItGeneratesPathInfoForInfoRefs(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');
        $this->assertEquals('/gpig/device/generic/goldfish.git/info/refs', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForGitUploadPack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-upload-pack');
        $this->assertEquals('/gpig/device/generic/goldfish.git/git-upload-pack', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForGitReceivePack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-receive-pack');
        $this->assertEquals('/gpig/device/generic/goldfish.git/git-receive-pack', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForHEAD(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/HEAD');
        $this->assertEquals('/gpig/device/generic/goldfish.git/HEAD', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForObjects(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/objects/f5/30d381822b12f76923bfba729fead27b378bec');
        $this->assertEquals(
            '/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec',
            $url->getPathInfo()
        );
    }

    public function testItGeneratesQueryString(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');
        $this->assertEquals('service=git-upload-pack', $url->getQueryString());
    }

    public function testItGeneratesAnEmptyQueryStringForGitUploadPack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-upload-pack');
        $this->assertEquals('', $url->getQueryString());
    }

    public function testItDetectsGitPushWhenServiceIsGitReceivePack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-receive-pack');
        $this->assertTrue($url->isWrite());
    }

    public function testItDetectsGitPushWhenURIIsGitReceivePack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-receive-pack');
        $this->assertTrue($url->isWrite());
    }

    public function testItRetrievesTheRepositoryWithExplicityDotGit(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/git-receive-pack');

        $this->assertEquals($this->goldfish_repository, $url->getRepository());
    }

    public function testItGeneratesPathInfoForObjectsWithExplicityDotGit(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec');
        $this->assertEquals(
            '/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec',
            $url->getPathInfo()
        );
    }

    public function testItGeneratesQueryStringWithExplicityDotGit(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/info/refs?service=git-upload-pack');
        $this->assertEquals('service=git-upload-pack', $url->getQueryString());
    }

    private function getUrl($url): Git_URL
    {
        return new Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $url
        );
    }
}
