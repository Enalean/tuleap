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

    /**
     * @var \Mockery\MockInterface|LastAccessKeyIdentifierStore
     */
    private $store;

    /**
     * @var \Mockery\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;

    /**
     * @var \Mockery\MockInterface|AccessKeyDAO
     */
    private $dao;

    /**
     * @var \Mockery\MockInterface|AccessKeyCreationNotifier
     */
    private $notifier;

    /**
     * @var AccessKeyCreator
     */
    private $access_key_creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store    = \Mockery::mock(LastAccessKeyIdentifierStore::class);
        $this->dao      = \Mockery::mock(AccessKeyDAO::class);
        $this->hasher   = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->notifier = \Mockery::mock(AccessKeyCreationNotifier::class);

        $this->access_key_creator = new AccessKeyCreator(
            $this->store,
            $this->dao,
            $this->hasher,
            $this->notifier
        );
    }

    public function testNewlyCreatedKeyIsCreatedAndAddedToTheLastAccessKeyIdentifierStore()
    {
        $this->hasher->shouldReceive('computeHash')->andReturns('hashed_identifier');
        $this->dao->shouldReceive('create')->once()->andReturns(1);
        $this->store->shouldReceive('storeLastGeneratedAccessKeyIdentifier')->once();
        $this->notifier->shouldReceive('notifyCreation')->once();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $this->access_key_creator->create($user, 'description', null);
    }

    public function testNewlyCreatedKeyIsCreatedWithAnExpirationDateAndAddedToTheLastAccessKeyIdentifierStore()
    {
        $this->hasher->shouldReceive('computeHash')->andReturns('hashed_identifier');
        $this->dao->shouldReceive('create')->once()->andReturns(1);
        $this->store->shouldReceive('storeLastGeneratedAccessKeyIdentifier')->once();
        $this->notifier->shouldReceive('notifyCreation')->once();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $expiration_date = new \DateTimeImmutable();

        $this->access_key_creator->create($user, 'description', $expiration_date);
    }

    public function testNewlyCreatedKeyAlreadyExpiredThrowsAnException()
    {
        $this->hasher->shouldReceive('computeHash')->never();
        $this->dao->shouldReceive('create')->never();
        $this->store->shouldReceive('storeLastGeneratedAccessKeyIdentifier')->never();
        $this->notifier->shouldReceive('notifyCreation')->never();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $expiration_date = new \DateTimeImmutable("yesterday");

        $this->expectException(AccessKeyAlreadyExpiredException::class);

        $this->access_key_creator->create($user, 'description', $expiration_date);
    }
}
