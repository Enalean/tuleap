<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Captcha;

require_once __DIR__ . '/bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ConfigurationRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testConfigurationIsRetrieved(): void
    {
        $dao = $this->createMock(DataAccessObject::class);
        $dao->method('getConfiguration')->willReturn([
            'site_key'   => 'site_key',
            'secret_key' => 'secret_key',
        ]);

        $configuration_retriever = new ConfigurationRetriever($dao);
        $configuration           = $configuration_retriever->retrieve();

        self::assertSame($configuration->getSiteKey(), 'site_key');
        self::assertSame($configuration->getSecretKey(), 'secret_key');
    }

    public function testAnExceptionIsThrownWhenConfigurationIsNotFound(): void
    {
        $dao = $this->createMock(DataAccessObject::class);
        $dao->method('getConfiguration')->willReturn(false);

        $configuration_retriever = new ConfigurationRetriever($dao);

        $this->expectException(ConfigurationNotFoundException::class);
        $configuration_retriever->retrieve();
    }
}
