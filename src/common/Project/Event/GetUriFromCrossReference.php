<?php
/**
 * Copyright (c) Enalean, 2020-present. All Rights Reserved.
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

namespace Tuleap\Project\Event;

use Tuleap\Event\Dispatchable;

class GetUriFromCrossReference implements Dispatchable
{
    public const NAME = "getUriFromCrossReference";

    /**
     * @var int
     */
    private $source_id;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $target_type;

    public function __construct(int $source_id, string $target_type)
    {
        $this->source_id   = $source_id;
        $this->target_type = $target_type;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function getTargetType(): string
    {
        return $this->target_type;
    }

    public function getSourceId(): int
    {
        return $this->source_id;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }
}
