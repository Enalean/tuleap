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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,
class UGroupSourceInitializationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ugroup = \Mockery::mock(\ProjectUGroup::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItQueriesTheDatabaseWhenDefaultValueIsFalse()
    {
        $this->ugroup->shouldReceive('getUgroupBindingSource')->once();
        $this->ugroup->isBound();
    }

    public function testItQueriesTheDatabaseOnlyOnce()
    {
        $this->ugroup->shouldReceive('getUgroupBindingSource')->once();
        $this->ugroup->shouldReceive('getUgroupBindingSource')->andReturns(null);
        $this->ugroup->isBound();
        $this->ugroup->isBound();
    }

    public function testItReturnsTrueWhenTheGroupIsBound()
    {
        $this->ugroup->shouldReceive('getUgroupBindingSource')->andReturns(\Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(666)->getMock());
        $this->assertTrue($this->ugroup->isBound());
    }

    public function testItReturnsFalseWhenTheGroupIsNotBound()
    {
        $this->ugroup->shouldReceive('getUgroupBindingSource')->andReturns(null);
        $this->assertFalse($this->ugroup->isBound());
    }
}
