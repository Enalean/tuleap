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
     * @throws \ReflectionException
     */
    #[\Override]
    public function getVariables(): array
    {
        $variables = [];
        foreach (ConstantWithConfigAttributesBuilder::get(...$this->classes) as $constant) {
            $default_values_attributes = $constant->getAttributes();

            $key   = null;
            $value = null;
            foreach ($default_values_attributes as $attribute) {
                $attribute_object = $attribute->newInstance();

                if ($attribute_object instanceof ConfigKey) {
                    $const_value = $constant->getValue();
                    if (is_string($const_value)) {
                        $key = $const_value;
                    }
                }

                if ($attribute_object instanceof FeatureFlagConfigKey) {
                    $const_value = $constant->getValue();
                    if (is_string($const_value)) {
                        $key = \ForgeConfig::FEATURE_FLAG_PREFIX . $const_value;
                    }
                }

                if ($attribute_object instanceof ConfigKeyType && $attribute_object->hasDefaultValue()) {
                    $constant_value = $constant->getValue();
                    assert(is_string($constant_value));
                    $value = $attribute_object->default_value;
                }
            }
            if ($key !== null && $value !== null) {
                $variables[$key] = $value;
            }
        }
        return $variables;
    }
}
