<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

class ParentOfArtifactCollection
{
    private $artifacts = [];
    private $is_graph;

    public function addArtifacts($artifacts)
    {
        $this->artifacts[] = $artifacts;
    }

    public function setIsGraph($is_graph)
    {
        $this->is_graph = $is_graph;
    }

    public function getArtifacts()
    {
        return $this->artifacts;
    }

    public function isGraph()
    {
        return $this->is_graph;
    }
}
