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

namespace Tuleap\Gitlab\Reference\Tag;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabTagReferenceSplitValuesBuilderTest extends TestCase
{
    private GitlabTagReferenceSplitValuesBuilder $builder;
    /**
     * @var TagReferenceSplitValuesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dao = $this->createMock(TagReferenceSplitValuesDao::class);

        $this->builder = new GitlabTagReferenceSplitValuesBuilder(
            $this->dao,
        );
    }

    public function testItRetrievesRepositoryNameAndTagName(): void
    {
        $this->dao->method('getAllTagsSplitValuesInProject')->willReturn(
            ['repository_name' => 'root/subgrp/repo01', 'tag_name' => 'v1.0.2'],
        );

        $split_values = $this->builder->splitRepositoryNameAndReferencedItemId('root/subgrp/repo01/v1.0.2', 101);

        self::assertSame('root/subgrp/repo01', $split_values->getRepositoryName());
        self::assertSame('v1.0.2', $split_values->getValue());
    }

    public function testItReturnsANotFoundReferenceIfDataNotFoundInDatabase(): void
    {
        $this->dao->method('getAllTagsSplitValuesInProject')->willReturn(null);

        $split_values = $this->builder->splitRepositoryNameAndReferencedItemId('root/subgrp/repo01/v1.0.2', 101);

        self::assertNull($split_values->getRepositoryName());
        self::assertNull($split_values->getValue());
    }
}
