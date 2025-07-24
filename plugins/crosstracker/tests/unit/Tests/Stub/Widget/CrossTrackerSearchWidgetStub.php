<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
namespace Tuleap\CrossTracker\Tests\Stub\Widget;

use Codendi_Request;
use Widget;

final class CrossTrackerSearchWidgetStub extends Widget
{
    private function __construct(int $id, private ?\Closure $callback)
    {
        parent::__construct($id);
    }

    /**
     *
     * @return null|false|int
     */
    #[\Override]
    public function create(Codendi_Request $request)
    {
        if ($this->callback !== null) {
            ($this->callback)($request);
        }
        return $this->id;
    }

    public static function withIdAndCallback(int $id, \Closure $callback): self
    {
        return new self($id, $callback);
    }

    public static function withDefault(): self
    {
        return new self(0, null);
    }
}
