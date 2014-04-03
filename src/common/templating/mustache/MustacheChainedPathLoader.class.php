<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class MustacheChainedPathLoader implements ArrayAccess {

    private $cache = array();
    private $template_directories = array();

    public function __construct(array $plugin_templates_dirs) {
        foreach ($plugin_templates_dirs as $directory) {
            if (! is_dir($directory)) {
                throw new InvalidArgumentException("$directory is not a valid directory");
            } else {
                $this->template_directories[] = $directory;
            }
        }
    }

    /**
     * @param  string $offset Name of partial
     * @return boolean
     */
    public function offsetExists($offset) {
        return (isset($this->cache[$offset]) || $this->getPartial($offset));
    }

    /**
     * @throws InvalidArgumentException if the given partial doesn't exist
     * @param  string $offset Name of partial
     * @return string Partial template contents
     */
    public function offsetGet($offset) {
        if (!isset($this->cache[$offset])) {
            $this->cache[$offset] = file_get_contents($this->getPartial($offset));
        }

        return $this->cache[$offset];
    }

    /**
     * MustacheLoader is an immutable filesystem loader. offsetSet throws a LogicException if called.
     *
     * @throws LogicException
     * @return void
     */
    public function offsetSet($offset, $value) {
        throw new LogicException('Unable to set offset: MustacheLoader is an immutable ArrayAccess object.');
    }

    /**
     * MustacheLoader is an immutable filesystem loader. offsetUnset throws a LogicException if called.
     *
     * @throws LogicException
     * @return void
     */
    public function offsetUnset($offset) {
        throw new LogicException('Unable to unset offset: MustacheLoader is an immutable ArrayAccess object.');
    }

    private function getPartial($file) {
        foreach ($this->template_directories as $dir) {
            $full_path = $this->pathName($dir, $file);
            if (file_exists($full_path)) {
                return $full_path;
            }
        }
        throw new InvalidArgumentException('Partial (' . $file . ') does not exist in path ' . implode(':', $this->template_directories));
    }

    /**
     * An internal helper for generating path names.
     *
     * @param  string $file Partial name
     * @return string File path
     */
    private function pathName($dir, $file) {
        return $dir . '/' . $file . '.mustache';
    }

}
