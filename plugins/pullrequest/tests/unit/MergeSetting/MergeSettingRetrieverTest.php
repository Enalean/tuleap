<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;

final class MergeSettingRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MergeSettingDAO&MockObject $dao;
    private \GitRepository&MockObject $git_repository;

    protected function setUp(): void
    {
        $this->dao            = $this->createMock(MergeSettingDAO::class);
        $this->git_repository = $this->createMock(\GitRepository::class);
    }

    public function testDefaultSettingIsRetrievedWhenNoSpecificInformationIsGiven(): void
    {
        $this->dao->method('getMergeSettingByRepositoryID')->willReturn(null);
        $this->git_repository->method('getId')->willReturn(10);

        $merge_setting_retriever = new MergeSettingRetriever($this->dao);
        $merge_setting           = $merge_setting_retriever->getMergeSettingForRepository($this->git_repository);

        self::assertInstanceOf(MergeSettingDefault::class, $merge_setting);
    }

    public function testSettingAreRetrievedWhenInformationExist(): void
    {
        $this->dao->method('getMergeSettingByRepositoryID')->willReturn(['merge_commit_allowed' => 1]);
        $this->git_repository->method('getId')->willReturn(10);

        $merge_setting_retriever = new MergeSettingRetriever($this->dao);
        $merge_setting           = $merge_setting_retriever->getMergeSettingForRepository($this->git_repository);

        self::assertInstanceOf(MergeSettingWithValue::class, $merge_setting);
    }
}
