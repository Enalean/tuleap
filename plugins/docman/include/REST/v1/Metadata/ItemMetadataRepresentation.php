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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

use Tuleap\REST\JsonCast;

class ItemMetadataRepresentation
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string|null
     */
    public $value;
    /**
     * @var string|null
     */
    public $post_processed_value;
    /**
     * @var array|null {@type Tuleap\Docman\REST\v1\Metadata\MetadataListValueRepresentation}
     */
    public $list_value;
    /**
     * @var bool
     */
    public $is_required;
    /**
     * @var bool
     */
    public $is_multiple_value_allowed;
    /**
     * @var string
     */
    public $short_name;

    public function __construct(
        string $name,
        string $type,
        bool $is_multiple_value_allowed,
        ?string $value,
        ?string $post_processed_value,
        ?array $list_value,
        bool $is_empty_allowed,
        string $short_name
    ) {
        $this->name                      = $name;
        $this->type                      = $type;
        $this->value                     = $value;
        $this->post_processed_value      = $post_processed_value;
        $this->is_required               = JsonCast::toBoolean(!$is_empty_allowed);
        $this->is_multiple_value_allowed = $is_multiple_value_allowed;
        $this->list_value                = $list_value;
        $this->short_name                = $short_name;
    }
}
