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
require_once __DIR__ . '/../bootstrap.php';

function aChangesetValueList()
{
    return new Test_Tracker_ChangesetValue_List_Builder();
}

class Test_Tracker_ChangesetValue_Builder
{
    protected $name;
    protected $id;
    protected $field;

    public function __construct($klass)
    {
        $this->name = $klass;
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return Tracker_Artifact_ChangesetValue
     */
    public function build()
    {
        $klass  = $this->name;
        $object = new $klass($this->id, mock('Tracker_Artifact_Changeset'), $this->field, null);
        return $object;
    }
}

class Test_Tracker_ChangesetValue_ArtifactLink_Builder extends Test_Tracker_ChangesetValue_Builder
{
    private $artifact_links;
    private $reverse_artifact_links;

    public function __construct()
    {
        parent::__construct('Tracker_Artifact_ChangesetValue_ArtifactLink');
        $this->field = anArtifactLinkField()->build();
    }

    public function withArtifactLinks($artifact_links)
    {
        $this->artifact_links = $artifact_links;
        return $this;
    }

    /**
     * @return Tracker_Artifact_ChangesetValue_ArtifactLink
     */
    public function build()
    {
        $object = new Tracker_Artifact_ChangesetValue_ArtifactLink($this->id, mock('Tracker_Artifact_Changeset'), $this->field, null, $this->artifact_links, $this->reverse_artifact_links);
        return $object;
    }
}

class Test_Tracker_ChangesetValue_List_Builder extends Test_Tracker_ChangesetValue_Builder
{
    private $list_values = array();

    public function __construct()
    {
        parent::__construct('Tracker_Artifact_ChangesetValue_List');
    }

    public function withValues(array $list_values)
    {
        $this->list_values = $list_values;
        return $this;
    }

    /**
     * @return Tracker_Artifact_ChangesetValue_List
     */
    public function build()
    {
        return new Tracker_Artifact_ChangesetValue_List($this->id, mock('Tracker_Artifact_Changeset'), $this->field, null, $this->list_values);
    }
}
