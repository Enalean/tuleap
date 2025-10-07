<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Events;

use Tuleap\Event\Dispatchable;

class PullRequestDiffRepresentationBuild implements Dispatchable
{
    public const string NAME = 'pullRequestDiffRepresentationBuild';

    /**
     * @var string
     */
    private $special_format = '';

    /**
     * @var string
     */
    private $object_dest;

    /**
     * @var string
     */
    private $object_src;

    public function __construct(string $object_dest, string $object_src)
    {
        $this->object_dest = $object_dest;
        $this->object_src  = $object_src;
    }

    public function setSpecialFormat(string $special_format): void
    {
        $this->special_format = $special_format;
    }

    public function getSpecialFormat(): string
    {
        return $this->special_format;
    }

    public function getObjectDest(): string
    {
        return $this->object_dest;
    }

    public function getObjectSrc(): string
    {
        return $this->object_src;
    }
}
