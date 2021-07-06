<?php
/**
 * Copyright (c) Enalean SAS, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\ForgeUpgrade;

use FilterIterator;

/**
 * Filter class that will be replaced by RegexIterator in php 5.2
 *
 * $regex = new RegexIterator($iter, '%^[0-9]+_(.*)\.php$%', RecursiveRegexIterator::GET_MATCH);
 */
class BucketFilter extends FilterIterator
{
    /**
     * @var string[]
     */
    protected array $includePaths = [];
    /**
     * @var string[]
     */
    protected array $excludePaths = [];

    public function addExclude(string $path): void
    {
        $this->excludePaths[] = $path;
    }

    public function addInclude(string $path): void
    {
        $this->includePaths[] = $path;
    }

    /**
     * @param string[] $paths
     */
    public function setIncludePaths(array $paths): void
    {
        $this->includePaths = $paths;
    }

    /**
     * @param string[] $paths
     */
    public function setExcludePaths(array $paths): void
    {
        $this->excludePaths = $paths;
    }

    /**
     * Match php upgrade scripts
     *
     * @return bool
     */
    public function accept()
    {
        $filePath = parent::current()->getPathname();

        $match = true;
        foreach ($this->includePaths as $path) {
            $match = $match && (strpos($filePath, $path) !== false);
        }

        foreach ($this->excludePaths as $path) {
            $match = $match && ! (strpos($filePath, $path) !== false);
        }

        return $match && preg_match('%^[0-9]+_(.*)\.php$%', basename($filePath));
    }
}
