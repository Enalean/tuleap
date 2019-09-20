<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\Admin;

use Backend;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ACLUpdater
{
    public const PARENT_DIR  = '..';
    public const CURRENT_DIR = '.';
    public const FILE        = 'file';
    public const DIRECTORY   = 'dir';

    /** @var Backend */
    private $backend;

    /** @var array */
    private $builder_map;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
        $this->builder_map = array(
            self::FILE      => new ACLBuilderForFile(),
            self::DIRECTORY => new ACLBuilderForDirectory(),
        );
    }

    public function recursivelyApplyACL($path, $http_user, $writers, $readers)
    {
        $this->updateACL($this->builder_map[self::DIRECTORY], $path, $http_user, $writers, $readers);
        $this->applyOnChildren($path, $http_user, $writers, $readers);
    }

    private function applyOnChildren($path, $http_user, $writers, $readers)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($this->fileCanBeUpdated($file->getFilename())) {
                $this->updateACL($this->getBuilderFromType($file), $file->getPathname(), $http_user, $writers, $readers);
            }
        }
    }

    private function getBuilderFromType($file)
    {
        return $this->builder_map[$file->isDir() ? self::DIRECTORY : self::FILE];
    }

    private function updateACL(ACLBuilder $builder, $path, $http_user, $writers, $readers)
    {
        $this->backend->resetacl($path);
        $this->backend->modifyacl(
            $builder->getACL($http_user, $writers, $readers),
            $path
        );
    }

    private function fileCanBeUpdated($filename)
    {
        return $filename !== self::PARENT_DIR && $filename !== self::CURRENT_DIR;
    }
}
