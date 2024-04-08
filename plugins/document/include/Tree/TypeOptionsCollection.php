<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Tuleap\Event\Dispatchable;

final class TypeOptionsCollection implements Dispatchable
{
    /**
     * @var array<string, SearchCriterionListOptionPresenter>
     */
    private array $options = [];

    public function __construct(public readonly \Project $project)
    {
    }

    public function addOption(SearchCriterionListOptionPresenter $option): self
    {
        $this->options[$option->value] = $option;

        return $this;
    }

    public function addOptionAfter(string $sibling, SearchCriterionListOptionPresenter $option): void
    {
        $sibling_position = 0;
        foreach ($this->options as $sibling_option) {
            $sibling_position++;
            if ($sibling === $sibling_option->value) {
                break;
            }
        }

        $this->options = array_merge(
            array_slice($this->options, 0, $sibling_position),
            [$option->value => $option],
            array_slice($this->options, $sibling_position)
        );
    }

    /**
     * @return list<SearchCriterionListOptionPresenter>
     */
    public function getOptions(): array
    {
        return array_values($this->options);
    }
}
