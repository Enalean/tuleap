<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

/**
 * @psalm-immutable
 */
final class CrossReferencePresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $url;
    /**
     * @var string
     */
    public $delete_url;
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $target_gid;
    /**
     * @var string
     */
    public $target_value;

    public function __construct(
        int $id,
        string $type,
        string $title,
        string $url,
        string $delete_url,
        int $project_id,
        string $value
    ) {
        $this->id           = $id;
        $this->type         = $type;
        $this->title        = $title;
        $this->url          = $url;
        $this->delete_url   = $delete_url;
        $this->target_gid   = $project_id;
        $this->target_value = $value;
    }
}
