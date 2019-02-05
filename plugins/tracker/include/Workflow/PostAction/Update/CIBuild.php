<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\Update\PostAction;

class CIBuild implements PostAction
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $job_url;

    public function __construct(?int $id, string $job_url)
    {
        $this->id      = $id;
        $this->job_url = $job_url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobUrl(): string
    {
        return $this->job_url;
    }

    public function accept(PostActionVisitor $visitor)
    {
        $visitor->visitCIBuild($this);
    }
}
