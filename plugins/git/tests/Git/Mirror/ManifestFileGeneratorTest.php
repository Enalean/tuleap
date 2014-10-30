<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
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

class Git_Mirror_ManifestFileGeneratorTest extends TuleapTestCase {

    private $current_time;
    private $manifest_directory;
    private $fixture_dir;
    /** @var Git_Mirror_ManifestFileGenerator */
    private $generator;
    /** @var GitRepository */
    private $kernel_repository;
    /** @var GitRepository */
    private $firefox_repository;
    /** @var Git_Mirror_Mirror */
    private $singapour_mirror;
    private $singapour_mirror_id = 1;
    private $manifest_file_for_singapour;

    public function setUp() {
        parent::setUp();
        $this->current_time       = $_SERVER['REQUEST_TIME'];
        $this->fixture_dir        = dirname(__FILE__) .'/_fixtures';
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

        $this->singapour_mirror = new Git_Mirror_Mirror(mock('PFUser'), $this->singapour_mirror_id, 'whatever');
        $this->manifest_file_for_singapour = $this->manifest_directory
            . "/manifest_mirror_{$this->singapour_mirror_id}.js.gz";

        $this->generator = new Git_Mirror_ManifestFileGenerator($this->manifest_directory);
    }

    public function tearDown() {
        `rm -rf $this->manifest_directory`;
        parent::tearDown();
    }

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

    private function getManifestContent($path) {
        $content = file_get_contents("compress.zlib://$path");

        return json_decode($content, true);
    }

    private function forgeExistingManifestFile($path) {
        file_put_contents(
            "compress.zlib://$path",
            '{"\/linux\/kernel.git":{"owner":null,"description":"Linux4ever","reference":null,"modified":1414684049}}'
        );
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