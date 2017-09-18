<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\Reference;

use TuleapTestCase;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;

require_once __DIR__.'/../bootstrap.php';

class ReferenceFactoryTest extends TuleapTestCase
{
    /**
     * @var ReferenceFactory
     */
    private $reference_factory;

    public function setUp() {
        parent::setUp();
        $this->pull_request_factory = mock('Tuleap\PullRequest\Factory');
        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->reference_retriever  = mock('Tuleap\PullRequest\Reference\ProjectReferenceRetriever');

        $this->reference_factory = new ReferenceFactory(
            $this->pull_request_factory,
            $this->repository_factory,
            $this->reference_retriever
        );

        $this->pull_request = new PullRequest(1, '', '', 42, 101, '', '', '', '', '', '');

        $project            = stub('Project')->getId()->returns(101);
        $this->repository   = aGitRepository()->withProject($project)->build();
    }

    public function itCreatesAReference() {
        $keyword         = 'pr';
        $pull_request_id = 1;

        stub($this->pull_request_factory)->getPullRequestById(1)->returns($this->pull_request);
        stub($this->repository_factory)->getRepositoryById(42)->returns($this->repository);
        stub($this->reference_retriever)->doesProjectReferenceWithKeywordExists($keyword, 101)->returns(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNotNull($reference);
        $this->assertIsA($reference, 'Tuleap\PullRequest\Reference\Reference');

    }

    public function itDoesNotCreateAReferenceIfPullRequestIdNotExisting() {
        $keyword         = 'pr';
        $pull_request_id = 1;

        stub($this->pull_request_factory)->getPullRequestById(1)->throws(new PullRequestNotFoundException());
        stub($this->repository_factory)->getRepositoryById(42)->returns($this->repository);
        stub($this->reference_retriever)->doesProjectReferenceWithKeywordExists($keyword, 101)->returns(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNull($reference);
    }

    public function itDoesNotCreateAReferenceIfRepositoryDoesNotExistAnymore() {
        $keyword         = 'pr';
        $pull_request_id = 1;

        stub($this->pull_request_factory)->getPullRequestById(1)->returns($this->pull_request);
        stub($this->repository_factory)->getRepositoryById(42)->returns(null);
        stub($this->reference_retriever)->doesProjectReferenceWithKeywordExists($keyword, 101)->returns(false);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNull($reference);
    }

    public function itDoesNotCreateAReferenceIfReferenceAlreadyExistInProject() {
        $keyword         = 'pr';
        $pull_request_id = 1;

        stub($this->pull_request_factory)->getPullRequestById(1)->returns($this->pull_request);
        stub($this->repository_factory)->getRepositoryById(42)->returns($this->repository);
        stub($this->reference_retriever)->doesProjectReferenceWithKeywordExists($keyword, 101)->returns(true);

        $reference = $this->reference_factory->getReferenceByPullRequestId($keyword, $pull_request_id);

        $this->assertNull($reference);
    }
}
