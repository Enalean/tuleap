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

final class ConstantWithConfigAttributesBuilder
{
    /**
     * @param class-string ...$class_names
     *
     * @return \Generator<\ReflectionClassConstant>
     * @throws \ReflectionException
     */
    public static function get(...$class_names): iterable
    {
        foreach ($class_names as $class) {
            $reflected_class = new \ReflectionClass($class);
            foreach ($reflected_class->getReflectionConstants() as $constant) {
                $constant_value = $constant->getValue();

                if (! is_string($constant_value)) {
                    continue;
                }

                $attributes = $constant->getAttributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() === ConfigKey::class || $attribute->getName() === FeatureFlagConfigKey::class) {
                        yield $constant;
                    }
                }
            }
        }
    }
}
