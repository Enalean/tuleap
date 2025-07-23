<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiOAuth2AppSecretGeneratorDBStoreTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&AppDao
     */
    private $dao;
    private MediaWikiOAuth2AppSecretGeneratorDBStore $secret_generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao              = $this->createStub(AppDao::class);
        $this->secret_generator = new MediaWikiOAuth2AppSecretGeneratorDBStore(
            new DBTransactionExecutorPassthrough(),
            $this->dao,
            new MediaWikiNewOAuth2AppBuilder(new SplitTokenVerificationStringHasher()),
            new SplitTokenVerificationStringHasher(),
            new class implements SplitTokenFormatter
            {
                #[\Override]
                public function getIdentifier(SplitToken $token): ConcealedString
                {
                    return new ConcealedString('test_value');
                }
            }
        );
    }

    public function testCreatesNewOAuth2App(): void
    {
        $this->dao->method('searchSiteLevelApps')->willReturn([]);
        $this->dao->method('create')->willReturn(789);

        $secret = $this->secret_generator->generateOAuth2AppSecret();

        self::assertEquals(789, $secret->getAppID());
        self::assertEquals('test_value', $secret->getSecret()->getString());
    }

    public function testReplacesOAuth2AppSecret(): void
    {
        $this->dao->method('searchSiteLevelApps')->willReturn([['id' => 123]]);
        $this->dao->method('updateSecret');

        $secret = $this->secret_generator->generateOAuth2AppSecret();

        self::assertEquals(123, $secret->getAppID());
        self::assertEquals('test_value', $secret->getSecret()->getString());
    }
}
