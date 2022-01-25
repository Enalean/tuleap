<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Config;

/**
 * This class is there to maintain backward compatibility with existing code that relies on \ForgeConfig::get() with a
 * variable that maps with a boolean but that was encoded with a string ('0'|'1') at the time. We want to avoid the need
 * to audit all usage of ::get(...) === '1' so this class is a shim to define a clean spec (bool) with compatibility with
 * existing code (not so clean).
 *
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
final class ConfigKeyLegacyBool implements ConfigKeyType
{
    public ?string $default_value = null;

    public function __construct(?bool $value)
    {
        if ($value !== null) {
            if ($value === true) {
                $this->default_value = '1';
            } else {
                $this->default_value = '0';
            }
        }
    }

    /**
     * @psalm-assert-if-true !null $this->default_value
     */
    public function hasDefaultValue(): bool
    {
        return $this->default_value !== null;
    }

    public function getSerializedRepresentation(string $name, string|int|bool $value): string
    {
        if (! is_string($value) && ! in_array($value, ['1', '0'], true)) {
            throw new \LogicException('Cannot accept non strinb/bool values');
        }
        return sprintf('$%s = \'%s\';%s', $name, $value, PHP_EOL);
    }
}
