<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\ApprovalTable;

use Docman_ApprovalTableFactory;
use Docman_ApprovalTableReviewerFactory;
use Exception;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Test\Builders\ApprovalReviewerTestBuilder;
use Tuleap\Docman\Test\Builders\ApprovalTableTestBuilder;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\RetrieveUserById;

#[DisableReturnValueGenerationForTestDoubles]
final class ApprovalTableUpdaterTest extends TestCase
{
    private Docman_ApprovalTableFactory&MockObject $table_factory;
    private Docman_ApprovalTableReviewerFactory&MockObject $reviewer_factory;

    #[Override]
    protected function setUp(): void
    {
        $this->table_factory    = $this->createMock(Docman_ApprovalTableFactory::class);
        $this->reviewer_factory = $this->createMock(Docman_ApprovalTableReviewerFactory::class);
    }

    private function update(RetrieveUserById $user_retriever, ApprovalTablePutRepresentation $representation): void
    {
        new ApprovalTableUpdater(
            $user_retriever,
            $this->table_factory,
            $this->reviewer_factory,
            new DBTransactionExecutorPassthrough(),
        )->update(ApprovalTableTestBuilder::anApprovalTable()
            ->withReviewers([ApprovalReviewerTestBuilder::aReviewer()->withId(104)->build()])
            ->build(), $representation);
    }

    public function testItThrowsIfOwnerDoesNotExists(): void
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->update(
            RetrieveUserByIdStub::withNoUser(),
            new ApprovalTablePutRepresentation(105, '', '', '', [], [], 0),
        );
    }

    public function testItThrowsIfDaoThrows(): void
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(500);

        $this->table_factory->expects($this->once())->method('updateTable')->willThrowException(new Exception('Dao is not happy'));

        $this->update(
            RetrieveUserByIdStub::withUser(UserTestBuilder::buildWithId(105)),
            new ApprovalTablePutRepresentation(105, 'enabled', '', 'disabled', [], [], 0),
        );
    }

    public function testItUpdates(): void
    {
        $this->table_factory->expects($this->once())->method('updateTable');

        $this->reviewer_factory->expects($this->once())->method('delUser')->with(104);
        $this->reviewer_factory->expects($this->once())->method('addUsers')->with([102, 103]);

        $this->update(
            RetrieveUserByIdStub::withUser(UserTestBuilder::buildWithId(105)),
            new ApprovalTablePutRepresentation(105, 'enabled', '', 'disabled', [102, 103], [], 0),
        );
    }
}
