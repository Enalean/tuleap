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

use Tuleap\CrossTracker\Widget\CreateWidget;

final readonly class CreateWidgetStub implements CreateWidget
{
    private function __construct(private int $widget_id)
    {
    }

    #[\Override]
    public function createWidget(): int
    {
        return $this->widget_id;
    }

    public static function withWidget(int $widget_id): self
    {
        return new self($widget_id);
    }
}
