<?php
/**
 * Copyright Enalean (c) 2014 - 2017. All rights reserved.
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

class Git_Mirror_ManifestFileGenerator_BaseTest extends TuleapTestCase {

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

    public function setUp() {
        parent::setUp();
        $this->current_time       = $_SERVER['REQUEST_TIME'];
        $this->time_in_the_past   = 1414684049;
        $this->fixture_dir        = $this->getTmpDir();
        $this->manifest_directory = $this->fixture_dir .'/manifests';
        mkdir($this->manifest_directory);

        $this->kernel_repository = aGitRepository()
            ->withPath('linux/kernel.git')
            ->withDescription('Linux4ever')
            ->build();
        $this->firefox_repository = aGitRepository()
            ->withPath('mozilla/firefox.git')
            ->withDescription('free and open-source web browser')
            ->build();

        $this->singapour_mirror = new Git_Mirror_Mirror(mock('PFUser'), $this->singapour_mirror_id, 'singapour.com', 'singapour', 'SNP');
        $this->manifest_file_for_singapour = $this->manifest_directory
            . "/manifest_mirror_{$this->singapour_mirror_id}.js.gz";

        $this->logger = mock('Logger');

        $this->generator = new Git_Mirror_ManifestFileGenerator($this->logger, $this->manifest_directory);
    }

    public function tearDown() {
        `rm -rf $this->manifest_directory`;
        parent::tearDown();
    }

    protected function getManifestContent($path) {
        $content = file_get_contents("compress.zlib://$path");

        return json_decode($content, true);
    }

    protected function forgeExistingManifestFile($path) {
        file_put_contents(
            "compress.zlib://$path",
            '{"\/linux\/kernel.git":{"owner":null,"description":"Linux4ever","reference":null,"modified":'. $this->time_in_the_past .'}}'
        );
    }

    protected function forgeExistingManifestFileWithGitoliteAdmin($path) {
        file_put_contents(
            "compress.zlib://$path",
            '{"\/gitolite-admin.git":{"owner":null,"description":"","reference":null,"modified":'. $this->time_in_the_past .'},"\/linux\/kernel.git":{"owner":null,"description":"Linux4ever","reference":null,"modified":'. $this->time_in_the_past .'}}'
        );
    }
}

class Git_Mirror_ManifestFileGenerator_removeTest extends Git_Mirror_ManifestFileGenerator_BaseTest {

    public function itDoesNotCreateManifestFileIfItDoesNotExist() {
        $this->assertFalse(is_file($this->manifest_file_for_singapour));

        $this->generator->removeRepositoryFromManifestFile($this->singapour_mirror, $this->kernel_repository->getPath());

        $this->assertFalse(is_file($this->manifest_file_for_singapour));
    }

    public function itRemovesRepositoryIfItIsInTheManifest() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->removeRepositoryFromManifestFile($this->singapour_mirror, $this->kernel_repository->getPath());

        $content = $this->getManifestContent($this->manifest_file_for_singapour);
        $this->assertFalse(isset($content["/linux/kernel.git"]));
    }

    public function itLogsDeletion() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->logger->expectCallCount('debug', 2);
        expect($this->logger)->debug('removing /linux/kernel.git from manifest of mirror singapour.com (id: 1)')->at(0);

        $this->generator->removeRepositoryFromManifestFile($this->singapour_mirror, $this->kernel_repository->getPath());
    }
}

class Git_Mirror_ManifestFileGenerator_addTest extends Git_Mirror_ManifestFileGenerator_BaseTest {

    public function itCreatesManifestFileIfItDoesNotExist() {
        $this->assertFalse(is_file($this->manifest_file_for_singapour));

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $this->assertTrue(is_file($this->manifest_file_for_singapour));
    }

    public function itAddsANewRepoIfManifestDoesNotExist() {
        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/linux/kernel.git"], array(
            "owner"       => null,
            "description" => "Linux4ever",
            "reference"   => null,
            "modified"    => $this->current_time
        ));
    }

    public function itLogsAddition() {
        $this->logger->expectCallCount('debug', 2);
        expect($this->logger)->debug('adding /linux/kernel.git to manifest of mirror singapour.com (id: 1)')->at(0);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function itAddsGitoliteAdminRepositoryIfManifestDoesNotExist() {
        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/gitolite-admin.git"], array(
            "owner"       => null,
            "description" => "",
            "reference"   => null,
            "modified"    => $this->current_time
        ));
    }

    public function itDoesNotUpdateExistingRepositoriesInformationIfManifestAlreadyExists() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->firefox_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/linux/kernel.git"], array(
            "owner"       => null,
            "description" => "Linux4ever",
            "reference"   => null,
            "modified"    => 1414684049
        ));
    }

    public function itAddsANewRepoIfManifestAlreadyExists() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->firefox_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/mozilla/firefox.git"], array(
            "owner"       => null,
            "description" => "free and open-source web browser",
            "reference"   => null,
            "modified"    => $this->current_time
        ));
    }

    public function itUpdatesDateToCurrentDateIfRepoAlreadyInManifest() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/linux/kernel.git"], array(
            "owner"       => null,
            "description" => "Linux4ever",
            "reference"   => null,
            "modified"    => $this->current_time
        ));
    }

    public function itLogsUpdate() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->logger->expectCallCount('debug', 2);
        expect($this->logger)->debug('updating /linux/kernel.git in manifest of mirror singapour.com (id: 1)')->at(0);

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function itDoesNotCrashIfFileDoesNotContainJson() {
        file_put_contents(
            "compress.zlib://$this->manifest_file_for_singapour",
            'not json file'
        );

        // Expect no error

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }

    public function itDoesNotCrashIfFileIsCorrupted() {
        file_put_contents(
            "$this->manifest_file_for_singapour",
            'corrupted file'
        );

        // Expect no error

        $this->generator->addRepositoryToManifestFile($this->singapour_mirror, $this->kernel_repository);
    }
}

class Git_Mirror_ManifestFileGenerator_ensureManifestContainsLatestInfoOfRepositoriesTest extends Git_Mirror_ManifestFileGenerator_BaseTest {

    public function itAddsAMissingRepository() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);

        $this->generator->ensureManifestContainsLatestInfoOfRepositories(
            $this->singapour_mirror,
            array($this->firefox_repository)
        );

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/mozilla/firefox.git"], array(
            "owner"       => null,
            "description" => "free and open-source web browser",
            "reference"   => null,
            "modified"    => $this->current_time
        ));
    }

    public function itRemovesANotNeededRepository() {
        $this->forgeExistingManifestFile($this->manifest_file_for_singapour);
        $content_before = $this->getManifestContent($this->manifest_file_for_singapour);
        $this->assertTrue(isset($content_before["/linux/kernel.git"]));

        expect($this->logger)->debug('removing /linux/kernel.git from manifest of mirror singapour.com (id: 1)')->once();

        $this->generator->ensureManifestContainsLatestInfoOfRepositories(
            $this->singapour_mirror,
            array()
        );

        $content_after = $this->getManifestContent($this->manifest_file_for_singapour);
        $this->assertFalse(isset($content_after["/linux/kernel.git"]));
    }
}

class Git_Mirror_ManifestFileGenerator_updateCurrentTimeOfRepositoryTest extends Git_Mirror_ManifestFileGenerator_BaseTest {

    public function itUpdatesDateToCurrentDateIfRepoAlreadyInManifest() {
        $this->forgeExistingManifestFileWithGitoliteAdmin($this->manifest_file_for_singapour);

        $this->generator->updateCurrentTimeOfRepository($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/linux/kernel.git"], array(
            "owner"       => null,
            "description" => "Linux4ever",
            "reference"   => null,
            "modified"    => $this->current_time
        ));
    }

    public function itDoesNotUpdateCurrentDateOfGitoliteAdmin() {
        $this->forgeExistingManifestFileWithGitoliteAdmin($this->manifest_file_for_singapour);

        $this->generator->updateCurrentTimeOfRepository($this->singapour_mirror, $this->kernel_repository);

        $content = $this->getManifestContent($this->manifest_file_for_singapour);

        $this->assertEqual($content["/gitolite-admin.git"], array(
            "owner"       => null,
            "description" => "",
            "reference"   => null,
            "modified"    => $this->time_in_the_past
        ));
    }
}
