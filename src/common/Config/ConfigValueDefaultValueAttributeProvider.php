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


final class ConfigValueDefaultValueAttributeProvider implements \ConfigValueProvider
{
    /**
     * @var class-string[]
     */
    private array $classes;

    /**
     * @param class-string ...$class_names
     */
    public function __construct(string ...$class_names)
    {
        $this->classes = $class_names;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        $variables = [];
        foreach (ConstantWithConfigAttributesBuilder::get(...$this->classes) as $constant) {
            $default_values_attributes = $constant->getAttributes();
            foreach ($default_values_attributes as $attribute) {
                $attribute_object = $attribute->newInstance();
                if ($attribute_object instanceof ConfigKeyType && $attribute_object->hasDefaultValue()) {
                    $constant_value = $constant->getValue();
                    assert(is_string($constant_value));
                    $variables[$constant_value] = $attribute_object->default_value;
                }
            }
        }
        return $variables;
    }
}
