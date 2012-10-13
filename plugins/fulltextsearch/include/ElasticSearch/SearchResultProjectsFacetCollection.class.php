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

class ElasticSearch_SearchResultProjectsFacetCollection implements IteratorAggregate, Countable {

    /**
     * @var array
     */
    private $facets = array();

    public function __construct(array $results, ProjectManager $project_manager, array $submitted_facets) {
        if (isset($results['terms'])) {
            foreach ($results['terms'] as $result) {
                $project = $project_manager->getProject($result['term']);
                if ($project && !$project->isError()) {
                    $checked = isset($submitted_facets['group_id']) && in_array($project->getGroupId(), $submitted_facets['group_id']);
                    $this->facets[] = new ElasticSearch_SearchResultProjectsFacet($project, $result['count'], $checked);
                }
            }
        }
    }

    /**
     * @see IteratorAggregate
     */
    public function getIterator() {
        return new ArrayIterator($this->facets);
    }

    /**
     * @see Countable
     */
    public function count() {
        return count($this->facets);
    }
}
?>
