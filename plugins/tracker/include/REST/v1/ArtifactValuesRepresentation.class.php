<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\REST\v1;

class ArtifactValuesRepresentation
{
    /**
     * @var int {@type int} {@required true}
     */
    public $field_id;

    /**
     * @var mixed {@required false}
     */
    public $value;

    /**
     * @var array {@required false}
     */
    public $bind_value_ids;

    /**
     * @var array {@required false}
     */
    public $links;

    /**
     * @var array | null {@required false} {@type \Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation}
     * @psalm-var LinkWithDirectionRepresentation[] | null
     */
    public ?array $all_links = null;

    /**
     * @var array {@required false}
     */
    public $parent;

    /**
     * @psalm-var bool|null
     * @var bool {@type boolean} {@required false}
     */
    public $is_autocomputed;

    /**
     * @var mixed {@required false}
     */
    public $manual_value;

    /**
     * @return array
     */
    public function toArray()
    {
        $array_representation = [];

        $array_representation['field_id'] = $this->field_id;

        if ($this->value !== null) {
            $array_representation['value'] = $this->value;
        }

        if ($this->bind_value_ids !== null) {
            $array_representation['bind_value_ids'] = $this->bind_value_ids;
        }

        if ($this->links !== null) {
            $array_representation['links'] = $this->links;
        }

        if ($this->parent !== null) {
            $array_representation['parent'] = $this->parent;
        }

        if ($this->is_autocomputed !== null) {
            $array_representation['is_autocomputed'] = $this->is_autocomputed;
        }

        if ($this->manual_value !== null) {
            $array_representation['manual_value'] = $this->manual_value;
        }

        return $array_representation;
    }
}
