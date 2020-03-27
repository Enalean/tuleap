<?php
/**
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tuleap\REST\JsonCast;

class NodeReferenceRepresentation
{

    public const ROUTE = 'testmanagement_nodes';

    public const NATURE_ARTIFACT = 'artifact';

    /**
     * @var int Id of node
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var string
     */
    public $ref_name;

    /**
     * @var string
     */
    public $ref_label;

    /**
     * @var string
     */
    public $color;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string | null
     */
    public $status_semantic;

    /**
     * @var string | null
     */
    public $status_label;

    /**
     * @var string
     */
    public $nature;

    public function build(
        int $id,
        string $nature,
        string $url,
        string $ref_name,
        string $ref_label,
        string $color,
        string $title,
        ?string $status_semantic,
        ?string $status_label
    ): void {
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
