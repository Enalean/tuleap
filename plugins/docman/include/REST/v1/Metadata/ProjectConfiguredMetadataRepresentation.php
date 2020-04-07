<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Metadata;

use Tuleap\REST\JsonCast;

class ProjectConfiguredMetadataRepresentation
{
    private const METADATA_TYPE_LABEL = [
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT   => 'text',
            PLUGIN_DOCMAN_METADATA_TYPE_STRING => 'string',
            PLUGIN_DOCMAN_METADATA_TYPE_DATE   => 'date',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST   => 'list'
        ];

    /**
     * @var string
     */
    public $short_name;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string | null
     */
    public $description;

    /**
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $is_required;

    /**
     * @var bool
     */
    public $is_multiple_value_allowed;

    /**
     * @var bool
     */
    public $is_used;

    /**
     * @var array | null  {@type Tuleap\Docman\REST\v1\Metadata\DocmanMetadataListValueRepresentation}
     */
    public $allowed_list_values;

    public function build(
        string $short_name,
        string $name,
        ?string $description,
        int $type,
        bool $is_empty_allowed,
        bool $is_multiple_value_allowed,
        bool $is_used,
        ?array $allowed_list_values
    ): void {
        $this->short_name                = $short_name;
        $this->name                      = $name;
        $this->description               = $description;
        $this->type                      = self::METADATA_TYPE_LABEL[$type];
        $this->is_required               = JsonCast::toBoolean(!$is_empty_allowed);
        $this->is_multiple_value_allowed = JsonCast::toBoolean($is_multiple_value_allowed);
        $this->is_used                   = JsonCast::toBoolean($is_used);
        $this->allowed_list_values       = $allowed_list_values;
    }
}
