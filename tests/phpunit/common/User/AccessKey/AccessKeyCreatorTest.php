<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class AccessKeyCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNewlyCreatedKeyIsCreatedAndAddedToTheLastAccessKeyIdentifierStore()
    {
        $store              = \Mockery::mock(LastAccessKeyIdentifierStore::class);
        $dao                = \Mockery::mock(AccessKeyDAO::class);
        $hasher             = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $notifier            = \Mockery::mock(AccessKeyCreationNotifier::class);
        $access_key_creator = new AccessKeyCreator($store, $dao, $hasher, $notifier);

        $hasher->shouldReceive('computeHash')->andReturns('hashed_identifier');
        $dao->shouldReceive('create')->once()->andReturns(1);
        $store->shouldReceive('storeLastGeneratedAccessKeyIdentifier')->once();
        $notifier->shouldReceive('notifyCreation')->once();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $access_key_creator->create($user, 'description');
    }
}
