<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\UserMapping;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class UserMappingManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItThrowsAnExceptionIfTheMappingCanNotBeFound(): void
    {
        $dao = \Mockery::spy(\Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao::class);
        $dao->shouldReceive('searchByProviderIdAndUserId')->andReturns(false);
        $provider = \Mockery::spy(\Tuleap\OpenIDConnectClient\Provider\Provider::class);
        $user = \Mockery::spy(\PFUser::class);
        $user_mapping_manager = new UserMappingManager($dao);

        $this->expectException('Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException');
        $user_mapping_manager->getByProviderAndUser($provider, $user);
    }
}
