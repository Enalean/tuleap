<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\SVN\Admin;

use Tuleap\SVNCore\Repository;

class ImmutableTag
{
    private $paths;
    private $repository;
    private $whitelist;

    public function __construct(Repository $repository, $paths, $whitelist)
    {
        $this->repository = $repository;
        $this->paths      = $paths;
        $this->whitelist  = $whitelist;
    }

    public static function buildEmptyImmutableTag(Repository $repository): self
    {
        return new self($repository, '', '');
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getPathsAsString()
    {
        return implode(PHP_EOL, $this->getPaths());
    }

    public function getPaths()
    {
        return $this->convertToArray($this->paths);
    }

    public function getWhitelist()
    {
        return $this->convertToArray($this->whitelist);
    }

    public function getWhitelistAsString()
    {
        return implode(PHP_EOL, $this->getWhitelist());
    }

    private function convertToArray($path)
    {
        if (! $path) {
            return [];
        }

        return explode(PHP_EOL, $path);
    }
}
