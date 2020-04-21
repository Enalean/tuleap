<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class AccessKeyRevokerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessKeyDAO
     */
    private $dao;
    /**
     * @var AccessKeyRevoker
     */
    private $revoker;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(AccessKeyDAO::class);

        $this->revoker = new AccessKeyRevoker($this->dao);
    }

    public function testUnusableAccessKeysAreRevoked(): void
    {
        $current_time = new \DateTimeImmutable('@10');

        $this->dao->shouldReceive('deleteByExpirationDate')->with(10)->once();
        $this->dao->shouldReceive('deleteKeysWithNoScopes')->once();

        $this->revoker->revokeUnusableUserAccessKeys($current_time);
    }
}
