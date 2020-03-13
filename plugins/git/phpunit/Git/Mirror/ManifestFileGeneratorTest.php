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
use Tuleap\TemporaryTestDirectory;

require_once __DIR__ . '/../../bootstrap.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ManifestFileGeneratorTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    protected $current_time;
    protected $manifest_directory;
    protected $fixture_dir;
    /** @var Git_Mirror_ManifestFileGenerator */
    protected $generator;
    /** @var GitRepository */
    protected $kernel_repository;
    /** @var GitRepository */
    protected $firefox_repository;
    /** @var Git_Mirror_Mirror */
    protected $singapour_mirror;
    protected $singapour_mirror_id = 1;
    protected $manifest_file_for_singapour;
    /** @var Logger */
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->current_time       = $_SERVER['REQUEST_TIME'];
        $this->time_in_the_past   = 1414684049;
        $this->fixture_dir        = $this->getTmpDir();
        $this->manifest_directory = $this->fixture_dir . '/manifests';
        mkdir($this->manifest_directory);

        $this->kernel_repository = $this->buildMockedRepository('linux/kernel.git', 'Linux4ever');
        $this->firefox_repository = $this->buildMockedRepository('mozilla/firefox.git', 'free and open-source web browser');

        $this->singapour_mirror = new Git_Mirror_Mirror(\Mockery::spy(\PFUser::class), $this->singapour_mirror_id, 'singapour.com', 'singapour', 'SNP');
        $this->manifest_file_for_singapour = $this->manifest_directory
            . "/manifest_mirror_{$this->singapour_mirror_id}.js.gz";

        $this->logger = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->generator = new Git_Mirror_ManifestFileGenerator($this->logger, $this->manifest_directory);
    }

    protected function tearDown(): void
    {
        `rm -rf $this->manifest_directory`;
        parent::tearDown();
    }

    private function buildMockedRepository(string $path, string $description): GitRepository
    {
        $repositrory = Mockery::mock(GitRepository::class);
        $repositrory->shouldReceive('getPath')->andReturn($path);
        $repositrory->shouldReceive('getDescription')->andReturn($description);

        return $repositrory;
    }

    protected function getManifestContent($path)
    {
        $content = file_get_contents("compress.zlib://$path");

        return json_decode($content, true);
    }

    protected function forgeExistingManifestFile($path): void
    {
        file_put_contents(
            "compress.zlib://$path",
            '{"\/linux\/kernel.git":{"owner":null,"description":"Linux4ever","reference":null,"modified":' . $this->time_in_the_past . '}}'
        );
    }

    protected function forgeExistingManifestFileWithGitoliteAdmin($path): void
    {
        file_put_contents(
            "compress.zlib://$path",
            '{"\/gitolite-admin.git":{"owner":null,"description":"","reference":null,"modified":' . $this->time_in_the_past . '},"\/linux\/kernel.git":{"owner":null,"description":"Linux4ever","reference":null,"modified":' . $this->time_in_the_past . '}}'
        );
    }

    public function testItDoesNotCreateManifestFileIfItDoesNotExist(): void
    {
        $this->assertFalse(is_file($this->manifest_file_for_singapour));

        $this->generator->removeRepositoryFromManifestFile($this->singapour_mirror, $this->kernel_repository->getPath());

        $this->assertFalse(is_file($this->manifest_file_for_singapour));
    }

    public function testItRemovesRepositoryIfItIsInTheManifest(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->removeRepositoryFromManifestFile($this->singapour_mirror, $this->kernel_repository->getPath());

        $content = $this->getManifestContent($this->manifest_file_for_singapour);
        $this->assertFalse(isset($content["/mozilla/firefox.git"]));
    }

    public function testItLogsDeletion(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->logger->shouldReceive('debug')->times(2);
        $this->logger->shouldReceive('debug')->with('removing /linux/kernel.git from manifest of mirror singapour.com (id: 1)')->ordered();

        $this->generator->removeRepositoryFromManifestFile($this->singapour_mirror, $this->kernel_repository->getPath());
    }

    public function testItCreatesManifestFileIfItDoesNotExist(): void
    {
        $this->assertFalse(is_file($this->manifest_file_for_singapour));

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $this->assertTrue(is_file($this->manifest_file_for_singapour));
    }

    public function testItAddsANewRepoIfManifestDoesNotExist(): void
    {
        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "Linux4ever",
                "reference"   => null,
                "modified"    => $this->current_time
            ),
            $content["/linux/kernel.git"]
        );
    }

    public function testItLogsAddition(): void
    {
        $this->logger->shouldReceive('debug')->times(2);
        $this->logger->shouldReceive('debug')->with('adding /linux/kernel.git to manifest of mirror singapour.com (id: 1)')->ordered();

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function testItAddsGitoliteAdminRepositoryIfManifestDoesNotExist(): void
    {
        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "",
                "reference"   => null,
                "modified"    => $this->current_time
            ),
            $content["/gitolite-admin.git"],
        );
    }

    public function testItDoesNotUpdateExistingRepositoriesInformationIfManifestAlreadyExists(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->firefox_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "Linux4ever",
                "reference"   => null,
                "modified"    => 1414684049
            ),
            $content["/linux/kernel.git"]
        );
    }

    public function testItAddsANewRepoIfManifestAlreadyExists(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->firefox_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "free and open-source web browser",
                "reference"   => null,
                "modified"    => $this->current_time
            ),
            $content["/mozilla/firefox.git"]
        );
    }

    public function testItUpdatesDateToCurrentDateIfRepoAlreadyInManifest(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "Linux4ever",
                "reference"   => null,
                "modified"    => $this->current_time
            ),
            $content["/linux/kernel.git"]
        );
    }

    public function testItLogsUpdate(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->logger->shouldReceive('debug')->times(2);
        $this->logger->shouldReceive('debug')->with('updating /linux/kernel.git in manifest of mirror singapour.com (id: 1)')->ordered();

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function testItDoesNotCrashIfFileDoesNotContainJson(): void
    {
        file_put_contents(
            "compress.zlib://$this->manifest_file_for_singapour",
            'not json file'
        );

        // Expect no error

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function testItDoesNotCrashIfFileIsCorrupted(): void
    {
        file_put_contents(
            "$this->manifest_file_for_singapour",
            'corrupted file'
        );

        // Expect no error

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function testItAddsAMissingRepository(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->ensureManifestContainsLatestInfoOfRepositories(
            $this->singapour_mirror,
            array($this->firefox_repository)
        );

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "free and open-source web browser",
                "reference"   => null,
                "modified"    => $this->current_time
            ),
            $content["/mozilla/firefox.git"],
        );
    }

    public function testItRemovesANotNeededRepository(): void
    {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);
        $content_before = $this->getManifestContent($this->manifest_file_for_singapour);
        $this->assertTrue(isset($content_before["/linux/kernel.git"]));

        $this->logger->shouldReceive('debug')->with('removing /linux/kernel.git from manifest of mirror singapour.com (id: 1)')->once();

        $this->generator->ensureManifestContainsLatestInfoOfRepositories(
            $this->singapour_mirror,
            array()
        );

        $content_after = $this->getManifestContent($this->manifest_file_for_singapour);
        $this->assertFalse(isset($content_after["/linux/kernel.git"]));
    }

    public function testItDoesNotUpdateCurrentDateOfGitoliteAdmin(): void
    {
        $this->forgeExistingManifestFileWithGitoliteAdmin($this->manifest_file_for_singapour);

        $this->generator->updateCurrentTimeOfRepository($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEquals(
            array(
                "owner"       => null,
                "description" => "",
                "reference"   => null,
                "modified"    => $this->time_in_the_past
            ),
            $content["/gitolite-admin.git"]
        );
    }
}
