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

final class ConfigSerializer
{
    /**
     * @param class-string[] $class_strings
     */
    public function save(string $path, int $mode, string $owner, string $group, string ...$class_strings): bool
    {
        if (! file_exists($path)) {
            touch($path);
        }
        chmod($path, $mode);
        chown($path, $owner);
        chgrp($path, $group);

        $conf_string  = '<?php' . PHP_EOL . PHP_EOL;
        $conf_string .= $this->getAllSerializedValues(...$class_strings);
        return file_put_contents($path, $conf_string) !== false;
    }

    /**
     * @param class-string[] $class_strings
     */
    private function getAllSerializedValues(string ...$class_strings): string
    {
        $content = '';
        foreach ($class_strings as $class_string) {
            $content .= $this->getAllSerializedValuesPerClass($class_string);
        }
        return $content;
    }

    /**
     * @param class-string $class_string
     */
    private function getAllSerializedValuesPerClass(string $class_string): string
    {
        $reflected_class = new \ReflectionClass($class_string);
        $constants       = $reflected_class->getReflectionConstants();
        $content         = '';
        foreach ($constants as $constant) {
            if (count($constant->getAttributes(ConfigKey::class)) === 1) {
                $constant_value = $constant->getValue();
                assert(is_string($constant_value));
                $content .= $this->getVariableForConfigFile($constants, $constant_value);
            }
        }
        return $content;
    }

    /**
     * @param \ReflectionClassConstant[]  $constants
     */
    private function getVariableForConfigFile(array $constants, string $constant_value): string
    {
        $title   = '';
        $var     = '';
        $comment = '';
        $value   = \ForgeConfig::get($constant_value);
        foreach ($constants as $constant) {
            if ($constant->getValue() === $constant_value) {
                foreach ($constant->getAttributes() as $attribute) {
                    $attribute_object = $attribute->newInstance();
                    if ($attribute_object instanceof ConfigKey) {
                        $title = '// ' . $attribute_object->summary . PHP_EOL;
                    }
                    if ($attribute_object instanceof ConfigKeyHelp) {
                        $comment = implode(PHP_EOL, array_map(static fn (string $line): string => '// ' . $line, explode(PHP_EOL, $attribute_object->text))) . PHP_EOL;
                    }
                    if ($attribute_object instanceof ConfigKeyType) {
                        $var = $attribute_object->getSerializedRepresentation($constant_value, $value);
                    }
                }

                if ($var === '') {
                    $var = sprintf('$%s = \'%s\';%s', $constant_value, $value, PHP_EOL);
                }
            }
        }
        if ($var === '') {
            throw new \LogicException('Constant ' . $constant_value . ' not found in class');
        }

        if ($title && $comment) {
            return $title . '//' . PHP_EOL . $comment . $var . PHP_EOL;
        }

        if ($title) {
            return $title . $var . PHP_EOL;
        }
        return $var . PHP_EOL;
    }
}
