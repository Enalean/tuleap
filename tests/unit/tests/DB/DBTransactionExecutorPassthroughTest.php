<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Test\DB;

use PHPUnit\Framework\TestCase;

final class DBTransactionExecutorPassthroughTest extends TestCase
{
    public function testCallableIsCalled(): void
    {
        $transation_executor = new DBTransactionExecutorPassthrough();

        $callable = new class {
            private $number_of_calls = 0;

            public function __invoke(): void
            {
                $this->number_of_calls++;
            }

            public function getNumberOfCalls(): int
            {
                return $this->number_of_calls;
            }
        };

        $transation_executor->execute($callable);

        $this->assertEquals(1, $callable->getNumberOfCalls());
    }

    public function testReturnCallableReturn(): void
    {
        $transaction_executor = new DBTransactionExecutorPassthrough();

        $this->assertEquals(
            42,
            $transaction_executor->execute(function () {
                return 42;
            })
        );
    }
}
