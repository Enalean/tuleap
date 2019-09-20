<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once __DIR__.'/../bootstrap.php';
class BurndownFieldBuilder
{

    private $tracker;
    private $hierarchy_factory;

    public function __construct()
    {
        $this->id            = null;
        $this->tracker_id    = null;
        $this->parent_id     = null;
        $this->name          = null;
        $this->label         = null;
        $this->description   = null;
        $this->use_it        = null;
        $this->scope         = null;
        $this->required      = null;
        $this->notifications = null;
        $this->rank          = null;
    }

    public function withTracker(Tracker $tracker)
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function withHierarchyFactory(Tracker_HierarchyFactory $hierarchy_factory)
    {
        $this->hierarchy_factory = $hierarchy_factory;
        return $this;
    }

    public function build()
    {
        $field = new Tracker_FormElement_Field_Burndown(
            $this->id,
            $this->tracker_id,
            $this->parent_id,
            $this->name,
            $this->label,
            $this->description,
            $this->use_it,
            $this->scope,
            $this->required,
            $this->notifications,
            $this->rank
        );

        if ($this->tracker) {
            $field->setTracker($this->tracker);
        }
        if ($this->hierarchy_factory) {
            $field->setHierarchyFactory($this->hierarchy_factory);
        }

        return $field;
    }
}

function aBurndownField()
{
    return new BurndownFieldBuilder();
}
