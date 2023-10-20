<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeSaver;

final class AccessKeyCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LastAccessKeyIdentifierStore
     */
    private $store;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyDAO
     */
    private $dao;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyScopeSaver
     */
    private $scope_saver;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyCreationNotifier
     */
    private $notifier;
    private AccessKeyCreator $access_key_creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store       = $this->createMock(LastAccessKeyIdentifierStore::class);
        $this->dao         = $this->createMock(AccessKeyDAO::class);
        $this->scope_saver = $this->createMock(AccessKeyScopeSaver::class);
        $this->hasher      = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->notifier    = $this->createMock(AccessKeyCreationNotifier::class);

        $this->access_key_creator = new AccessKeyCreator(
            $this->store,
            $this->dao,
            $this->hasher,
            $this->scope_saver,
            new DBTransactionExecutorPassthrough(),
            $this->notifier
        );
    }

    public function testNewlyCreatedKeyIsCreatedAndAddedToTheLastAccessKeyIdentifierStore(): void
    {
        $authentication_scope = $this->createMock(AuthenticationScope::class);
        $this->hasher->method('computeHash')->willReturn('hashed_identifier');
        $this->dao->expects(self::once())->method('create')->willReturn(1);
        $this->scope_saver->expects(self::once())->method('saveKeyScopes')->with(1, $authentication_scope);
        $this->store->expects(self::once())->method('storeLastGeneratedAccessKeyIdentifier');
        $this->notifier->expects(self::once())->method('notifyCreation');

        $user = UserTestBuilder::aUser()->withId(102)->build();

        $this->access_key_creator->create($user, 'description', null, $authentication_scope);
    }

    public function testNewlyCreatedKeyIsCreatedWithAnExpirationDateAndAddedToTheLastAccessKeyIdentifierStore(): void
    {
        $authentication_scope = $this->createMock(AuthenticationScope::class);
        $this->hasher->method('computeHash')->willReturn('hashed_identifier');
        $this->dao->expects(self::once())->method('create')->willReturn(1);
        $this->store->expects(self::once())->method('storeLastGeneratedAccessKeyIdentifier');
        $this->scope_saver->expects(self::once())->method('saveKeyScopes')->with(1, $authentication_scope);
        $this->notifier->expects(self::once())->method('notifyCreation');

        $user = UserTestBuilder::aUser()->withId(102)->build();

        $expiration_date = new \DateTimeImmutable();

        $this->access_key_creator->create($user, 'description', $expiration_date, $authentication_scope);
    }

    public function testNewlyCreatedKeyAlreadyExpiredThrowsAnException(): void
    {
        $this->hasher->expects(self::never())->method('computeHash');
        $this->dao->expects(self::never())->method('create');
        $this->store->expects(self::never())->method('storeLastGeneratedAccessKeyIdentifier');
        $this->notifier->expects(self::never())->method('notifyCreation');

        $user = UserTestBuilder::aUser()->withId(102)->build();

        $expiration_date = new \DateTimeImmutable("yesterday");

        $this->expectException(AccessKeyAlreadyExpiredException::class);

        $this->access_key_creator->create($user, 'description', $expiration_date, $this->createMock(AuthenticationScope::class));
    }
}
