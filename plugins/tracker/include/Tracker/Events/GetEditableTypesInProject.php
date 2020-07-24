<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Events;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;

class GetEditableTypesInProject implements Dispatchable
{
    public const NAME = 'tracker_get_editable_type_in_project';

    /**
     * @var array
     */
    private $types = [];

    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    public function addType(NaturePresenter $type)
    {
        $this->types[] = $type;
    }
}
