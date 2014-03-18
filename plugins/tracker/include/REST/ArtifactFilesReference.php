<?php
/**
  * Copyright (c) Enalean, 2014. All rights reserved
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

namespace Tuleap\Tracker\REST\Artifact;

use \Tuleap\REST\JsonCast;
use \Tracker_Artifact_Attachment_TemporaryFile;

class ArtifactFilesReference {

    const ROUTE = 'artifact_files';

    /**
     * @var int ID of the artifact_file
     */
    public $id;

    /**
     * @var string URI of the artifact_file
     */
    public $uri;

    /**
     *
     * @param Tracker_Artifact_Attachment_TemporaryFile $file
     * @return \Tuleap\Tracker\REST\Artifact\ArtifactFilesReference
     */
    public function build(Tracker_Artifact_Attachment_TemporaryFile $file) {
        $this->id  = JsonCast::toInt($file->getId());
        $this->uri = self::ROUTE . '/' . $this->id;

        return $this;
    }
}
?>
