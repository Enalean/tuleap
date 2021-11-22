<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\NaturePresenter;

class ArtifactLinkTypeCanBeUnused implements Dispatchable
{
    public const NAME = 'tracker_artifact_link_can_be_unused';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var NaturePresenter
     */
    private $type;

    /**
     * @var bool
     */
    private $can_be_unused = false;

    /**
     * @var bool
     */
    private $is_type_checked_by_plugin = false;

    public function __construct(Project $project, NaturePresenter $type)
    {
        $this->project = $project;
        $this->type    = $type;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getType(): NaturePresenter
    {
        return $this->type;
    }

    public function setTypeIsCheckedByPlugin(): void
    {
        $this->is_type_checked_by_plugin = true;
    }

    public function setTypeIsUnusable(): void
    {
        $this->can_be_unused = true;
    }

    public function canTypeBeUnused(): bool
    {
        return $this->can_be_unused;
    }

    public function doesPluginCheckedTheType(): bool
    {
        return $this->is_type_checked_by_plugin;
    }
}
