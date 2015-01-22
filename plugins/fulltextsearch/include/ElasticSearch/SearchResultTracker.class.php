<?php
/**
 * Copyright (c) STMicroelectronics 2013. All Rights Reserved.
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

class ElasticSearch_SearchResultTracker extends ElasticSearch_SearchResult {

    const TYPE_IDENTIFIER = 'tracker';

    public $item_title;
    public $url;

    public function __construct(array $hit, Project $project, Tracker_Artifact $artifact) {
        $this->setItemTitle($artifact);
        $this->url        = '/plugins/tracker/?aid='.$artifact->getId();
        parent::__construct($hit, $project);
    }

    public function type() {
        return self::TYPE_IDENTIFIER;
    }

    private function setItemTitle(Tracker_Artifact $artifact) {
        $title = $artifact->getTitle();
        $xref  = $artifact->getXRef();
        $class = $artifact->getTracker()->getColor();

        $this->item_title = $title;
        $this->item_class = $class;
        $this->item_xref  = $xref;
    }

}
