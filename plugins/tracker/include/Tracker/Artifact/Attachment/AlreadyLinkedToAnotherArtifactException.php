<?php
/**
  * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
declare(strict_types=1);

class Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException extends Exception
{
    public function __construct(int $file_id, Tracker_Artifact $linked_artifact)
    {
        parent::__construct('File #' . $file_id . ' is already linked to artifact #' . $linked_artifact->getId());
    }
}
