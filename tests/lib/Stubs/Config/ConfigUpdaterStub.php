<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\Config;

use Tuleap\Config\ConfigUpdater;
use Tuleap\Cryptography\ConcealedString;

final class ConfigUpdaterStub implements ConfigUpdater
{
    /**
     * @var array<string, ConcealedString|string>
     */
    private array $updated_config = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function set(string $key, ConcealedString|string $value): void
    {
        $this->updated_config[$key] = $value;
    }

    /**
     * @return array<string, ConcealedString|string>
     */
    public function getAllUpdatedConfig(): array
    {
        return $this->updated_config;
    }

    public function getUpdatedConfig(string $key): ConcealedString|string
    {
        return $this->updated_config[$key];
    }
}
