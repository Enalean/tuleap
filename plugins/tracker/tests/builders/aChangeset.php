<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

function aChangeset()
{
    return new Test_Tracker_Changeset_Builder();
}

function aChangesetComment()
{
    return new Test_Tracker_Artifact_Changeset_Comment_Builder();
}

class Test_Tracker_Changeset_Builder
{
    private $id;
    private $email;
    private $submitted_on;
    private $submitted_by;
    private $artifact;
    private $comment;

    public function __construct()
    {
        $this->comment = aChangesetComment()->build();
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     *
     * @param Tracker_Artifact_Changeset_Comment|-1 $comment
     * @return Test_Tracker_Changeset_Builder
     */
    public function withComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function withSubmittedBy($submitted_by)
    {
        $this->submitted_by = $submitted_by;
        return $this;
    }

    public function withSubmittedOn($submitted_on)
    {
        $this->submitted_on = $submitted_on;
        return $this;
    }

    public function withArtifact(Tracker_Artifact $artifact)
    {
        $this->artifact = $artifact;
        return $this;
    }

    /**
     * @return Tracker_Artifact_Changeset
     */
    public function build()
    {
        $changeset = new Tracker_Artifact_Changeset($this->id, $this->artifact, $this->submitted_by, $this->submitted_on, $this->email);
        if ($this->comment !== null) {
            $changeset->setLatestComment($this->comment);
        }
        return $changeset;
    }
}

class Test_Tracker_Artifact_Changeset_Comment_Builder
{
    private $id;
    private $changeset;
    private $comment_type_id;
    private $canned_response_id;
    private $submitted_by;
    private $submitted_on;
    private $body;
    private $bodyFormat;
    private $parent_id;

    public function withText($text)
    {
        $this->body = $text;
        return $this;
    }

    public function build()
    {
        return new Tracker_Artifact_Changeset_Comment(
            $this->id,
            $this->changeset,
            $this->comment_type_id,
            $this->canned_response_id,
            $this->submitted_by,
            $this->submitted_on,
            $this->body,
            $this->bodyFormat,
            $this->parent_id
        );
    }
}
