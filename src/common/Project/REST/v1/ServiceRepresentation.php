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

/**
 * @psalm-immutable
 */
class ServiceRepresentation
{
    public const ROUTE = 'project_services';

    /**
     * @var int {@required false}
     */
    public $id;
    /**
     * @var string {@required false}
     */
    public $uri;
    /**
     * @var string {@required false}
     */
    public $name;
    /**
     * @var string {@required false}
     */
    public $label;
    /**
     * @var bool {@required true}
     */
    public $is_enabled;
    /**
     * @var string {@required false}
     */
    public $icon = '';

    private function __construct(int $id, string $name, string $label, bool $is_enabled, string $icon)
    {
        $this->id         = $id;
        $this->uri        = self::ROUTE . '/' . urlencode((string) $id);
        $this->name       = $name;
        $this->label      = $label;
        $this->is_enabled = $is_enabled;
        $this->icon       = $icon;
    }

    public static function build(Service $service): self
    {
        return new self(
            JsonCast::toInt($service->getId()),
            $service->getShortName(),
            $service->getInternationalizedName(),
            JsonCast::toBoolean($service->isUsed()),
            $service->getIconName()
        );
    }
}
