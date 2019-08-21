<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_URL_GitSmartHTTPTest extends TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();
        $this->project_manager     = mock('ProjectManager');
        $this->repository_factory  = mock('GitRepositoryFactory');
        $this->gpig_project        = mock('Project');
        stub($this->gpig_project)->getId()->returns($this->gpig_project_id);
        stub($this->gpig_project)->getUnixName()->returns($this->gpig_project_name);

        $this->goldfish_repository = aGitRepository()
            ->withProject($this->gpig_project)
            ->withName('device/generic/goldfish')
            ->build();

        stub($this->repository_factory)
            ->getByProjectNameAndPath(
                $this->gpig_project_name,
                'device/generic/goldfish.git'
            )->returns($this->goldfish_repository);

        stub($this->repository_factory)
            ->getRepositoryById($this->repository_id)
            ->returns($this->goldfish_repository);

        $this->apache_repository = aGitRepository()
            ->withProject($this->gpig_project)
            ->withName('apache-2.5')
            ->build();

        stub($this->repository_factory)
            ->getByProjectNameAndPath(
                $this->gpig_project_name,
                'apache-2.5.git'
            )->returns($this->apache_repository);

        stub($this->project_manager)
            ->getProject($this->gpig_project_id)
            ->returns($this->gpig_project);
    }

    public function itRetrievesTheRepository()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');

        $this->assertEqual($url->getRepository(), $this->goldfish_repository);
    }

    public function itGeneratesPathInfoForInfoRefs()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');
        $this->assertEqual($url->getPathInfo(), '/gpig/device/generic/goldfish.git/info/refs');
    }

    public function itGeneratesPathInfoForGitUploadPack()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-upload-pack');
        $this->assertEqual($url->getPathInfo(), '/gpig/device/generic/goldfish.git/git-upload-pack');
    }

    public function itGeneratesPathInfoForGitReceivePack()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-receive-pack');
        $this->assertEqual($url->getPathInfo(), '/gpig/device/generic/goldfish.git/git-receive-pack');
    }

    public function itGeneratesPathInfoForHEAD()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/HEAD');
        $this->assertEqual($url->getPathInfo(), '/gpig/device/generic/goldfish.git/HEAD');
    }

    public function itGeneratesPathInfoForObjects()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/objects/f5/30d381822b12f76923bfba729fead27b378bec');
        $this->assertEqual($url->getPathInfo(), '/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec');
    }

    public function itGeneratesQueryString()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack');
        $this->assertEqual($url->getQueryString(), 'service=git-upload-pack');
    }

    public function itGeneratesAnEmptyQueryStringForGitUploadPack()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-upload-pack');
        $this->assertEqual($url->getQueryString(), '');
    }

    public function itDetectsGitPushWhenServiceIsGitReceivePack()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-receive-pack');
        $this->assertTrue($url->isWrite());
    }

    public function itDetectsGitPushWhenURIIsGitReceivePack()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish/git-receive-pack');
        $this->assertTrue($url->isWrite());
    }

    public function itRetrievesTheRepositoryWithExplicityDotGit()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/git-receive-pack');

        $this->assertEqual($url->getRepository(), $this->goldfish_repository);
    }

    public function itGeneratesPathInfoForObjectsWithExplicityDotGit()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec');
        $this->assertEqual($url->getPathInfo(), '/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec');
    }

    public function itGeneratesQueryStringWithExplicityDotGit()
    {
        $url = $this->getUrl('/plugins/git/gpig/device/generic/goldfish.git/info/refs?service=git-upload-pack');
        $this->assertEqual($url->getQueryString(), 'service=git-upload-pack');
    }

    /** @return Git_URL */
    private function getUrl($url)
    {
        return new Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $url
        );
    }
}
