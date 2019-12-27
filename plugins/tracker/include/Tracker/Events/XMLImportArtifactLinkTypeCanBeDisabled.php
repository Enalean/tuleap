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

class XMLImportArtifactLinkTypeCanBeDisabled implements Dispatchable
{
    public const NAME = 'tracker_xml_import_artifact_link_can_be_disabled';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $type_name;

    /**
     * @var bool
     */
    private $can_be_unused = false;

    /**
     * @var bool
     */
    private $is_type_checked_by_plugin = false;

    /**
     * @var string
     */
    private $message = '';

    public function __construct(Project $project, $type_name)
    {
        $this->project   = $project;
        $this->type_name = $type_name;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->type_name;
    }

    public function setTypeIsCheckedByPlugin()
    {
        $this->is_type_checked_by_plugin = true;
    }

    public function setTypeIsUnusable()
    {
        $this->can_be_unused = true;
    }

    /**
     * @return bool
     */
    public function canTypeBeUnused()
    {
        return $this->can_be_unused;
    }

    /**
     * @return bool
     */
    public function doesPluginCheckedTheType()
    {
        return $this->is_type_checked_by_plugin;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
