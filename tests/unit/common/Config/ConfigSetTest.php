<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Config;

use Tuleap\Test\Builders\Config\ConfigKeyMetadataBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ConfigSetTest extends TestCase
{
    public function testExceptionIfKeyDoesNotExist(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $config_set = new ConfigSet($this->getConfigKeys([]), $config_dao);

        $this->expectException(UnknownConfigKeyException::class);
        $config_set->set('unknown', 'value');
    }

    public function testExceptionIfKeyIsNotModifiable(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $config_set = new ConfigSet($this->getConfigKeys(['foo' => $this->getMetadataNotModifiable()]), $config_dao);

        $this->expectException(InvalidConfigKeyException::class);
        $config_set->set('foo', 'value');
    }

    public function testSaveConfigKeyValue(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::once())
            ->method('save')
            ->with('foo', 'value');

        $config_set = new ConfigSet($this->getConfigKeys(['foo' => $this->getMetadataModifiable()]), $config_dao);
        $config_set->set('foo', 'value');
    }

    public function testExceptionIfValueIsNotValid(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $config_set = new ConfigSet($this->getConfigKeys(['foo' => $this->getMetadataWithValidator()]), $config_dao);

        $this->expectException(InvalidConfigKeyValueException::class);
        $config_set->set('foo', 'value');
    }

    public function testExceptionIfSecretIsNotValid(): void
    {
        $config_dao = $this->createMock(ConfigDao::class);
        $config_dao->expects(self::never())
            ->method('save');

        $config_set = new ConfigSet($this->getConfigKeys(['foo' => $this->getSecretMetadataWithValidator()]), $config_dao);

        $this->expectException(InvalidConfigKeyValueException::class);
        $config_set->set('foo', 'value');
    }

    /**
     * @param array<string, ConfigKeyMetadata> $metadata
     */
    private function getConfigKeys(
        array $metadata,
    ): KeyMetadataProvider & KeysThatCanBeModifiedProvider {
        $keys_that_can_be_modified_provider = new class ($metadata) implements KeyMetadataProvider, KeysThatCanBeModifiedProvider {
            public function __construct(private readonly array $metadata)
            {
            }

            public function getKeyMetadata(string $key): ConfigKeyMetadata
            {
                if (! isset($this->metadata[$key])) {
                    throw new UnknownConfigKeyException($key);
                }

                return $this->metadata[$key];
            }

            public function getKeysThatCanBeModified(): array
            {
                return [];
            }
        };
        \assert($keys_that_can_be_modified_provider instanceof KeyMetadataProvider && $keys_that_can_be_modified_provider instanceof KeysThatCanBeModifiedProvider);

        return $keys_that_can_be_modified_provider;
    }

    private function getMetadataModifiable(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aModifiableMetadata()->build();
    }

    private function getMetadataNotModifiable(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aNonModifiableMetadata()->build();
    }

    private function getMetadataWithValidator(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aModifiableMetadata()->withValidator(ConfigSetTestValueValidator::buildSelf())->build();
    }

    private function getSecretMetadataWithValidator(): ConfigKeyMetadata
    {
        return ConfigKeyMetadataBuilder::aModifiableMetadata()->withSecretValidator(ConfigSetTestSecretValidator::buildSelf())->build();
    }
}
