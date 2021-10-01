<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Config;

use ReflectionClass;

final class ConfigValueEnvironmentProvider implements \ConfigValueProvider
{
    private const ENV_VARIABLE_PREFIX = 'TULEAP_';

    /**
     * @var class-string[]
     */
    private array $classes;

    /**
     * @param class-string ...$class_name
     */
    public function __construct(string ...$class_name)
    {
        $this->classes = $class_name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        $variables = [];
        foreach ($this->classes as $class) {
            try {
                $reflected_class = new ReflectionClass($class);
                foreach ($reflected_class->getReflectionConstants() as $constant) {
                    $constant_value = $constant->getValue();
                    if (! is_string($constant_value)) {
                        continue;
                    }

                    $attributes = $constant->getAttributes(ConfigKey::class);
                    if (count($attributes) !== 1) {
                        continue;
                    }

                    $env_value = getenv(self::ENV_VARIABLE_PREFIX . strtoupper($constant_value));
                    if ($env_value === false) {
                        continue;
                    }

                    $variables[$constant_value] = $env_value;
                }
            } catch (\ReflectionException) {
            }
        }
        return $variables;
    }
}
