<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1;

use Service;
use Tuleap\REST\JsonCast;

class ServiceRepresentation
{
    public const ROUTE = 'project_services';

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $uri;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $label;
    /**
     * @var bool
     */
    public $is_enabled;

    public function build(Service $service): void
    {
        $this->id         = JsonCast::toInt($service->getId());
        $this->uri        = self::ROUTE . '/' . urlencode((string) $this->id);
        $this->name       = $service->getShortName();
        $this->label      = $service->getInternationalizedName();
        $this->is_enabled = JsonCast::toBoolean($service->isUsed());
    }
}
