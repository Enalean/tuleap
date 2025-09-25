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

namespace Tuleap\Git;

use Git_URL;
use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class URLTest extends TestCase
{
    private ProjectManager&MockObject $project_manager;
    private GitRepositoryFactory&MockObject $repository_factory;
    private GitRepository $goldfish_repository;
    private GitRepository $apache_repository;
    private string $gpig_project_name = 'gpig';
    private int $gpig_project_id      = 111;
    private int $repository_id        = 43;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_manager    = $this->createMock(ProjectManager::class);
        $this->repository_factory = $this->createMock(GitRepositoryFactory::class);
        $gpig_project             = ProjectTestBuilder::aProject()->withId($this->gpig_project_id)->withUnixName($this->gpig_project_name)->build();

        $this->goldfish_repository = $this->buildRepository($gpig_project, 'device/generic/goldfish');
        $this->apache_repository   = $this->buildRepository($gpig_project, 'apache-2.5');

        $this->repository_factory->method('getByProjectNameAndPath')->with($this->gpig_project_name, self::isString())
            ->willReturnCallback(fn(string $project_name, string $path) => match ($path) {
                'device/generic/goldfish.git' => $this->goldfish_repository,
                'apache-2.5.git'              => $this->apache_repository,
                default                       => null,
            });
        $this->repository_factory->method('getRepositoryById')->willReturnCallback(fn(int $id) => match ($id) {
            $this->repository_id => $this->goldfish_repository,
            default              => null,
        });

        $this->project_manager->method('getProject')->with($this->gpig_project_id)->willReturn($gpig_project);
    }

    private function buildRepository(Project $project, string $name): GitRepository
    {
        return GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName($name)->build();
    }

    public function testItRetrievesTheRepository(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');

        self::assertEquals($this->goldfish_repository, $url->getRepository());
    }

    public function testItGeneratesPathInfoForInfoRefs(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');
        self::assertEquals('/gpig/device/generic/goldfish.git/info/refs', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForGitUploadPack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-upload-pack');
        self::assertEquals('/gpig/device/generic/goldfish.git/git-upload-pack', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForGitReceivePack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-receive-pack');
        self::assertEquals('/gpig/device/generic/goldfish.git/git-receive-pack', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForHEAD(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/HEAD');
        self::assertEquals('/gpig/device/generic/goldfish.git/HEAD', $url->getPathInfo());
    }

    public function testItGeneratesPathInfoForObjects(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/objects/f5/30d381822b12f76923bfba729fead27b378bec');
        self::assertEquals(
            '/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec',
            $url->getPathInfo()
        );
    }

    public function testItGeneratesQueryString(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');
        self::assertEquals('service=git-upload-pack', $url->getQueryString());
    }

    public function testItGeneratesAnEmptyQueryStringForGitUploadPack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-upload-pack');
        self::assertEquals('', $url->getQueryString());
    }

    public function testItDetectsGitPushWhenServiceIsGitReceivePack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-receive-pack');
        self::assertTrue($url->isWrite());
    }

    public function testItDetectsGitPushWhenURIIsGitReceivePack(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-receive-pack');
        self::assertTrue($url->isWrite());
    }

    public function testItRetrievesTheRepositoryWithExplicityDotGit(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/git-receive-pack');

        self::assertEquals($this->goldfish_repository, $url->getRepository());
    }

    public function testItGeneratesPathInfoForObjectsWithExplicityDotGit(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec');
        self::assertEquals(
            '/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec',
            $url->getPathInfo()
        );
    }

    public function testItGeneratesQueryStringWithExplicityDotGit(): void
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/info/refs?service=git-upload-pack');
        self::assertEquals('service=git-upload-pack', $url->getQueryString());
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
