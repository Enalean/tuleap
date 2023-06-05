<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn\Source;

use Symfony\Component\Uid\Uuid;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\TrustPathLoader;

final class WebAuthnCredentialSourceDaoTest extends TestCase
{
    private WebAuthnCredentialSourceDao $dao;

    private const USER_ID = 101;

    protected function setUp(): void
    {
        $this->dao = new WebAuthnCredentialSourceDao();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM webauthn_credential_source');
    }

    public function testSaveAndRetrieve(): void
    {
        $source = $this->generateSource(self::USER_ID);

        // It does not find before saving
        $retrieved = $this->dao->findOneByCredentialId('gbleskefe');
        self::assertNull($retrieved);
        $sources = $this->dao->findAllForUserEntity(
            PublicKeyCredentialUserEntity::create('Marianna Deberry', (string) self::USER_ID, 'mdeberry')
        );
        self::assertEmpty($sources);
        $sources = $this->dao->getAllByUserId(self::USER_ID);
        self::assertEmpty($sources);

        $this->dao->saveCredentialSource($source);

        $retrieved = $this->dao->findOneByCredentialId($source->getPublicKeyCredentialId());
        self::assertNotNull($retrieved);
        $this->assertSourceEquals($source, $retrieved);

        $sources = $this->dao->getAllByUserId((int) $source->getUserHandle());
        self::assertCount(1, $sources);
        $retrieved = $sources[0];
        $this->assertSourceEquals($source, $retrieved->getSource());
        self::assertNotNull($retrieved->getName());
        self::assertNotNull($retrieved->getCreatedAt());
        self::assertNotNull($retrieved->getLastUse());
    }

    public function testSaveEditNameThenFind(): void
    {
        $source = $this->generateSource(self::USER_ID);

        $this->dao->saveCredentialSource($source);
        $this->dao->changeCredentialSourceName($source->getPublicKeyCredentialId(), 'MyAwesomeKey');

        $sources = $this->dao->getAllByUserId((int) $source->getUserHandle());
        self::assertCount(1, $sources);
        self::assertSame('MyAwesomeKey', $sources[0]->getName());
    }

    public function testSaveWithNameThenFind(): void
    {
        $source = $this->generateSource(self::USER_ID);

        $this->dao->saveCredentialSourceWithName($source, 'MyAwesomeKey');

        $sources = $this->dao->getAllByUserId((int) $source->getUserHandle());
        self::assertCount(1, $sources);
        self::assertSame('MyAwesomeKey', $sources[0]->getName());
    }

    public function testDelete(): void
    {
        $source = $this->generateSource(self::USER_ID);
        $this->dao->saveCredentialSource($source);

        $this->dao->deleteCredentialSource($source->getPublicKeyCredentialId());

        $retrieved = $this->dao->findOneByCredentialId($source->getPublicKeyCredentialId());
        self::assertNull($retrieved);
    }

    private function assertSourceEquals(PublicKeyCredentialSource $expected, PublicKeyCredentialSource $actual): void
    {
        self::assertSame($expected->getPublicKeyCredentialId(), $actual->getPublicKeyCredentialId());
        self::assertSame($expected->getType(), $actual->getType());
        self::assertEqualsCanonicalizing($expected->getTransports(), $actual->getTransports());
        self::assertSame($expected->getAttestationType(), $actual->getAttestationType());
        self::assertEquals($expected->getTrustPath(), $actual->getTrustPath());
        self::assertEquals($expected->getAaguid(), $actual->getAaguid());
        self::assertSame($expected->getCredentialPublicKey(), $actual->getCredentialPublicKey());
        self::assertSame($expected->getUserHandle(), $actual->getUserHandle());
        self::assertSame($expected->getCounter(), $actual->getCounter());
        self::assertEquals($expected->getOtherUI(), $actual->getOtherUI());
    }

    private function generateSource(int $user_id): PublicKeyCredentialSource
    {
        return PublicKeyCredentialSource::create(
            random_bytes(32),
            'type',
            [],
            'attestationType',
            TrustPathLoader::loadTrustPath(['type' => 'Webauthn\\TrustPath\\EmptyTrustPath']),
            Uuid::v4(),
            random_bytes(16),
            (string) $user_id,
            0
        );
    }
}
