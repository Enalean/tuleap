<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\MergeSetting;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class MergeSettingRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $git_repository;

    protected function setUp(): void
    {
        $this->dao            = \Mockery::mock(MergeSettingDAO::class);
        $this->git_repository = \Mockery::mock(\GitRepository::class);
    }

    public function testDefaultSettingIsRetrievedWhenNoSpecificInformationIsGiven()
    {
        $this->dao->shouldReceive('getMergeSettingByRepositoryID')->andReturns(null);
        $this->git_repository->shouldReceive('getId')->andReturns(10);

        $merge_setting_retriever = new MergeSettingRetriever($this->dao);
        $merge_setting           = $merge_setting_retriever->getMergeSettingForRepository($this->git_repository);

        $this->assertInstanceOf(MergeSettingDefault::class, $merge_setting);
    }

    public function testSettingAreRetrievedWhenInformationExist()
    {
        $this->dao->shouldReceive('getMergeSettingByRepositoryID')->andReturns(['merge_commit_allowed' => 1]);
        $this->git_repository->shouldReceive('getId')->andReturns(10);

        $merge_setting_retriever = new MergeSettingRetriever($this->dao);
        $merge_setting           = $merge_setting_retriever->getMergeSettingForRepository($this->git_repository);

        $this->assertInstanceOf(MergeSettingWithValue::class, $merge_setting);
    }
}
