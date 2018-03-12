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

namespace Tuleap\DynamicCredentials\Credential;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CredentialRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCredentialsAreMarkedAsRevoked()
    {
        $dao = Mockery::mock(CredentialDAO::class);
        $dao->shouldReceive('revokeByIdentifier')->andReturn(1, 0);
        $identifier_extractor = Mockery::mock(CredentialIdentifierExtractor::class);
        $identifier_extractor->shouldReceive('extract');

        $credential_remover = new CredentialRemover($dao, $identifier_extractor);
        $this->assertTrue($credential_remover->revokeByUsername('username'));
        $this->assertFalse($credential_remover->revokeByUsername('username'));
    }
}
