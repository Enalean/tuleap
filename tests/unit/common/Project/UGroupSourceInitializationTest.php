<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Project;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UGroupSourceInitializationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \ProjectUGroup&MockObject $ugroup;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->ugroup = $this->createPartialMock(\ProjectUGroup::class, [
            'getUgroupBindingSource',
        ]);
    }

    public function testItQueriesTheDatabaseWhenDefaultValueIsFalse(): void
    {
        $this->ugroup->expects($this->once())->method('getUgroupBindingSource');
        $this->ugroup->isBound();
    }

    public function testItQueriesTheDatabaseOnlyOnce(): void
    {
        $this->ugroup->expects($this->once())->method('getUgroupBindingSource');
        $this->ugroup->isBound();
        $this->ugroup->isBound();
    }

    public function testItReturnsTrueWhenTheGroupIsBound(): void
    {
        $this->ugroup->method('getUgroupBindingSource')
            ->willReturn(ProjectUGroupTestBuilder::aCustomUserGroup(666)->build());
        self::assertTrue($this->ugroup->isBound());
    }

    public function testItReturnsFalseWhenTheGroupIsNotBound(): void
    {
        $this->ugroup->method('getUgroupBindingSource')->willReturn(null);
        self::assertFalse($this->ugroup->isBound());
    }
}
