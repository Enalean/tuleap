<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

/**
 * Presenter of the child of an artifact
 */
class Tracker_ArtifactChildPresenter {

    /** @var string */
    public $xref;

    /** @var string */
    public $title;

    /** @var int */
    public $id;

    /** @var string */
    public $url;

    /** @var string */
    public $status;

    /**
     * @param Tracker_Artifact        $artifact The child
     * @param Tracker_Semantic_Status $semantic The status semantic used by the corresponding tracker
     */
    public function __construct(Tracker_Artifact $artifact, Tracker_Semantic_Status $semantic) {
        $base_url = get_server_url();

        $this->xref   = $artifact->getXRef();
        $this->title  = $artifact->getTitle();
        $this->id     = $artifact->getId();
        $this->url    = $base_url . $artifact->getUri();
        $this->status = $semantic->getStatus($artifact);
    }
}
?>
