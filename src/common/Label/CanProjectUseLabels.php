<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Label;

use Project;
use Tuleap\Event\Dispatchable;

class CanProjectUseLabels implements Dispatchable
{
    const NAME = 'canProjectUseLabels';
    /**
     * @var bool
     */
    private $are_labels_usable;
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project           = $project;
        $this->are_labels_usable = false;
    }

    /**
     * @return bool
     */
    public function areLabelsUsable()
    {
        return $this->are_labels_usable;
    }

    public function projectCanUseLabels()
    {
        $this->are_labels_usable = true;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }
}
