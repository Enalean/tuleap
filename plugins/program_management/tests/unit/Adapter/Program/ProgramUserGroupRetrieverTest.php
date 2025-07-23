<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Luracast\Restler\RestException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramUserGroupDoesNotExistException;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\Project\REST\UserGroupRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramUserGroupRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramUserGroupRetriever $program_user_group_retriever;
    private ProgramForAdministrationIdentifier $program;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserGroupRetriever
     */
    private $user_group_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->user_group_retriever         = $this->createMock(UserGroupRetriever::class);
        $this->program_user_group_retriever = new ProgramUserGroupRetriever($this->user_group_retriever);
        $this->program                      = ProgramForAdministrationIdentifierBuilder::buildWithId(102);
    }

    public function testItRetrievesTheUgroupId(): void
    {
        $this->user_group_retriever->method('getExistingUserGroup')->with('102_3')
            ->willReturn(new \ProjectUGroup(['ugroup_id' => 3, 'group_id' => 102]));

        $ugroup_id = $this->program_user_group_retriever->getProjectUserGroupId('102_3', $this->program);
        self::assertSame(3, $ugroup_id);
    }

    public function testItThrowsWhenUgroupCannotBeFound(): void
    {
        $this->user_group_retriever->method('getExistingUserGroup')->willThrowException(new RestException(404));
        $this->expectException(ProgramUserGroupDoesNotExistException::class);
        $this->program_user_group_retriever->getProjectUserGroupId('404_3', $this->program);
    }

    public function testRejectIfUgroupIsNotInProgram(): void
    {
        $this->user_group_retriever->method('getExistingUserGroup')->with('123_3')->willReturn(
            new \ProjectUGroup(['ugroup_id' => 3, 'group_id' => 123])
        );
        $this->expectException(ProgramUserGroupDoesNotExistException::class);
        $this->program_user_group_retriever->getProjectUserGroupId('123_3', $this->program);
    }
}
