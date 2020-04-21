<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Test\PHPUnit;

use PHPUnit\Framework\TestCase;

final class XMLEntityLoadingNotRestoredToDefaultStateTest extends TestCase
{
    public function testXMLEntityLoadingDisabledAtTheBeginningOfTheTestSuiteLikeInRealEnvironment(): void
    {
        libxml_disable_entity_loader(false);

        $hook = new XMLEntityLoadingNotRestoredToDisabledState();

        $hook->executeBeforeFirstTest();

        $this->assertTrue(libxml_disable_entity_loader(true));
    }

    public function testDoNothingIfTestDoesNotTouchXMLEntityLoading(): void
    {
        $hook = new XMLEntityLoadingNotRestoredToDisabledState();

        $this->expectNotToPerformAssertions();
        $hook->executeAfterTest('Something::test', 0.1);
    }

    public function testThrowsIfTestChangeXMLEntityLoading(): void
    {
        $hook = new XMLEntityLoadingNotRestoredToDisabledState();

        libxml_disable_entity_loader(false);
        $this->expectException(\RuntimeException::class);
        $hook->executeAfterTest('Something::testXMLEntity', 0.1);
    }
}
