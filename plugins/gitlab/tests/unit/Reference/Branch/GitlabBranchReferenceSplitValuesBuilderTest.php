<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference\Branch;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabBranchReferenceSplitValuesBuilderTest extends TestCase
{
    private GitlabBranchReferenceSplitValuesBuilder $builder;
    /**
     * @var BranchReferenceSplitValuesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dao = $this->createMock(BranchReferenceSplitValuesDao::class);

        $this->builder = new GitlabBranchReferenceSplitValuesBuilder(
            $this->dao,
        );
    }

    public function testItRetrievesRepositoryNameAndBranchName(): void
    {
        $this->dao->method('getAllBranchesSplitValuesInProject')->willReturn(
            ['repository_name' => 'root/subgrp/repo01', 'branch_name' => 'my/branch'],
        );

        $split_values = $this->builder->splitRepositoryNameAndReferencedItemId('root/subgrp/repo01/my/branch', 101);

        self::assertSame('root/subgrp/repo01', $split_values->getRepositoryName());
        self::assertSame('my/branch', $split_values->getValue());
    }

    public function testItReturnsANotFoundReferenceIfDataNotFoundInDatabase(): void
    {
        $this->dao->method('getAllBranchesSplitValuesInProject')->willReturn(null);

        $split_values = $this->builder->splitRepositoryNameAndReferencedItemId('root/subgrp/repo01/branch01', 101);

        self::assertNull($split_values->getRepositoryName());
        self::assertNull($split_values->getValue());
    }
}
