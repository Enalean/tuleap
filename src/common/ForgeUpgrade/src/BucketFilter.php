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

/**
 * Filter class that will be replaced by RegexIterator in php 5.2
 *
 * $regex = new RegexIterator($iter, '%^[0-9]+_(.*)\.php$%', RecursiveRegexIterator::GET_MATCH);
 */
class ForgeUpgrade_BucketFilter extends FilterIterator
{

    protected $includePaths = [];
    protected $excludePaths = [];

    public function addExclude($path)
    {
        $this->excludePaths[] = $path;
    }

    public function addInclude($path)
    {
        $this->includePaths[] = $path;
    }

    public function setIncludePaths($paths)
    {
        $this->includePaths = $paths;
    }

    public function setExcludePaths($paths)
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
            $match &= (strpos($filePath, $path) !== false);
        }

        foreach ($this->excludePaths as $path) {
            $match &= ! (strpos($filePath, $path) !== false);
        }

        $match &= preg_match('%^[0-9]+_(.*)\.php$%', basename($filePath));
        return $match;
    }
}
