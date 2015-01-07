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

class ElasticSearch_SearchResultWiki extends ElasticSearch_SearchResult {
    public $item_title;
    public $url;

    public function __construct(array $hit, Project $project) {
        $page_name        = $hit['fields']['page_name'][0];
        $project_id       = $hit['fields']['group_id'][0];

        $page_identifier  = urlencode($page_name);

        $this->item_title = $page_name;
        $this->url        = '/wiki/index.php?group_id='.$project_id.'&pagename='.$page_identifier;
        $this->highlight  = isset($hit['highlight']['content']) ? array_shift($hit['highlight']['content']) : '';

        parent::__construct($hit, $project);
    }

    public function type() {
        return 'wiki';
    }
}
