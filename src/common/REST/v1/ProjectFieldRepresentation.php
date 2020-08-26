<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
declare(strict_types=1);

namespace Tuleap\REST\v1;

use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldLabelBuilder;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class ProjectFieldRepresentation
{
    public const ROUTE = "project_fields";

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $rank;

    /**
     * @var bool
     */
    public $is_required;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $type;

    public function __construct(array $field)
    {
        $this->id          = JsonCast::toInt($field['group_desc_id']);
        $this->name        = DescriptionFieldLabelBuilder::getFieldTranslatedName($field['desc_name']);
        $this->description = DescriptionFieldLabelBuilder::getFieldTranslatedName($field['desc_description']);
        $this->rank        = JsonCast::toInt($field['desc_rank']);
        $this->type        = $field['desc_type'];
        $this->is_required = JsonCast::toBoolean($field['desc_required']);
    }
}
