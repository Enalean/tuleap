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

class Tracker_Artifact_View_Child {

    /** @var Tracker_Artifact */
    private $child;
    
    /** @var Tracker_Semantic_Status */
    private $semantics;

    public function __construct(Tracker_Artifact $child, Tracker_Semantic_Status $semantics) {
        $this->child = $child;
        $this->semantics = $semantics;
    }

    public function toArray() {
        if (! $this->child->getStatus()) {
            $status = null;
        } else {
            $status = ( in_array($this->child->getStatus(), $this->semantics->getOpenLabels()) ) ? 1 : 0;
        }

        $base_url = get_server_url();

        return array(
            'xref'  => $this->child->getXRef(),
            'title' => $this->child->getTitle(),
            'id'    => $this->child->getId(),
            'url'   => $base_url.$this->child->getUri(),
            'status'=> $status
        );
    }
}
?>
