<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace Tuleap\Trafficlights\REST\v1;

use Tuleap\REST\JsonCast;

class NodeReferenceRepresentation {

    const ROUTE = 'trafficlights_nodes';

    const NATURE_ARTIFACT = 'artifact';

    /**
     * @var int Id of node
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    public $ref_name;

    public $ref_label;

    public $color;

    public $title;

    public $url;

    public $status_semantic;

    public $status_label;

    public $nature;

    public function build(
        $id,
        $nature,
        $url,
        $ref_name,
        $ref_label,
        $color,
        $title,
        $status_semantic,
        $status_label
    ) {
        $this->id              = JsonCast::toInt($id);
        $this->uri             = self::ROUTE . '/' . $this->id;
        $this->nature          = $nature;
        $this->url             = $url;
        $this->ref_name        = $ref_name;
        $this->ref_label       = $ref_label;
        $this->color           = $color;
        $this->title           = $title;
        $this->status_semantic = $status_semantic;
        $this->status_label    = $status_label;
    }
}
