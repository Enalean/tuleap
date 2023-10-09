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

namespace Tuleap\Layout\NewDropdown;

use Tuleap\Option\Option;

final class CurrentContextSectionToHeaderOptionsInserter
{
    /**
     * @param Option<NewDropdownLinkSectionPresenter> $current_context_section
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    public function addLinkToCurrentContextSection(
        string $section_label,
        NewDropdownLinkPresenter $link,
        Option $current_context_section,
    ): Option {
        return $current_context_section->mapOr(
            function (NewDropdownLinkSectionPresenter $current_context_section) use ($link): Option {
                return $this->createNewCurrentContextSection(
                    $current_context_section->label,
                    array_merge(
                        $current_context_section->links,
                        [$link]
                    )
                );
            },
            $this->createNewCurrentContextSection($section_label, [$link]),
        );
    }

    /**
     * @param NewDropdownLinkPresenter[] $links
     * @return Option<NewDropdownLinkSectionPresenter>
     */
    private function createNewCurrentContextSection(
        string $section_label,
        array $links,
    ): Option {
        return Option::fromValue(
            new NewDropdownLinkSectionPresenter(
                $section_label,
                $links,
            )
        );
    }
}
