<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\DB;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ThereIsAnOngoingTransactionCheckerTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        \ForgeConfig::setFeatureFlag(ThereIsAnOngoingTransactionChecker::FEATURE_FLAG, 0);
    }

    public function testNotInTransaction(): void
    {
        \ForgeConfig::setFeatureFlag(ThereIsAnOngoingTransactionChecker::FEATURE_FLAG, 1);
        $this->expectNotToPerformAssertions();

        $checker = new ThereIsAnOngoingTransactionChecker();
        $checker->checkNoOngoingTransaction();
    }

    public function testInTransaction(): void
    {
        \ForgeConfig::setFeatureFlag(ThereIsAnOngoingTransactionChecker::FEATURE_FLAG, 1);
        $this->expectException(\RuntimeException::class);

        $checker = new ThereIsAnOngoingTransactionChecker();
        DBFactory::getMainTuleapDBConnection()->getDB()->tryFlatTransaction(static fn () => $checker->checkNoOngoingTransaction());
    }

    public function testDoesNothingWhenInTransactionWithoutTheFeatureFlag(): void
    {
        $this->expectNotToPerformAssertions();

        $checker = new ThereIsAnOngoingTransactionChecker();
        DBFactory::getMainTuleapDBConnection()->getDB()->tryFlatTransaction(static fn () => $checker->checkNoOngoingTransaction());
    }

    public function testWhenTransactionCommitted(): void
    {
        \ForgeConfig::setFeatureFlag(ThereIsAnOngoingTransactionChecker::FEATURE_FLAG, 1);
        $this->expectNotToPerformAssertions();

        $checker = new ThereIsAnOngoingTransactionChecker();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->tryFlatTransaction(static fn (): mixed => $db->run('SELECT 1'));
        $checker->checkNoOngoingTransaction();
    }
}
