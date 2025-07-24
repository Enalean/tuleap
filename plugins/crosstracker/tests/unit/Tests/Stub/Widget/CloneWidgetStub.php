<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

final class CloneWidgetStub implements \Tuleap\CrossTracker\Widget\CloneWidget
{
    private int $call_count = 0;

    /**
     * @param array<int, int> $widget_map
     */
    private function __construct(private readonly array $widget_map)
    {
    }

    #[\Override]
    public function cloneWidget(int $template_widget_id): int
    {
        $this->call_count++;
        if (! isset($this->widget_map[$template_widget_id])) {
            throw new \LogicException('Expected to find template widget id, but the stub was not prepared with it');
        }
        return $this->widget_map[$template_widget_id];
    }

    /**
     * @param array<int, int> $widget_map
     */
    public static function withClonedWidgetMap(array $widget_map): self
    {
        return new self($widget_map);
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
