<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field;

use Psr\SimpleCache\CacheInterface;

class XMLCriteriaValueCache implements CacheInterface
{
    /**
     * @var XMLCriteriaValueCache[]
     */
    private static $instance;

    /**
     * @var array
     */
    private $value = [];

    public static function instance(int $object_identifier): self
    {
        if (! isset(self::$instance[$object_identifier])) {
            self::$instance[$object_identifier] = new self();
        }
        return self::$instance[$object_identifier];
    }

    public static function clearInstances(): void
    {
        self::$instance = [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->value[$key];
    }

    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): true
    {
        $this->value[$key] = $value;
        return true;
    }

    public function delete(string $key): bool
    {
        //Does nothing
        return false;
    }

    public function clear(): bool
    {
        $this->value = [];
        return true;
    }

    public function getMultiple(iterable $keys, $default = null): array
    {
        //Does Nothing
        return [];
    }

    public function setMultiple(iterable $values, int|\DateInterval|null $ttl = null): false
    {
        //Does Nothing
        return false;
    }

    public function deleteMultiple(iterable $keys): false
    {
        //Does Nothing
        return false;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->value);
    }
}
