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

namespace Tuleap\layout\NewDropdown;

final class CurrentContextSectionToHeaderOptionsInserter
{
    public function addLinkToCurrentContextSection(
        string $section_label,
        NewDropdownLinkPresenter $link,
        array &$header_options,
    ): void {
        if (isset($header_options['new_dropdown_current_context_section'])) {
            $this->addLinkToExistingCurrentContextSection($link, $header_options);
        } else {
            $this->createNewCurrentContextSection($section_label, [$link], $header_options);
        }
    }

    /**
     * @param NewDropdownLinkPresenter[] $links
     */
    private function createNewCurrentContextSection(
        string $section_label,
        array $links,
        array &$header_options,
    ): void {
        $header_options['new_dropdown_current_context_section'] = new NewDropdownLinkSectionPresenter(
            $section_label,
            $links,
        );
    }

    private function addLinkToExistingCurrentContextSection(
        NewDropdownLinkPresenter $link,
        array &$header_options,
    ): void {
        $this->createNewCurrentContextSection(
            $header_options['new_dropdown_current_context_section']->label,
            array_merge(
                $header_options['new_dropdown_current_context_section']->links,
                [$link],
            ),
            $header_options
        );
    }
}
