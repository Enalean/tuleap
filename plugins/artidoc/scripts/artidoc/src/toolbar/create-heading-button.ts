/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { HeadingsButton } from "@/toolbar/HeadingsButton";
import { TAG } from "@/toolbar/HeadingsButton";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";

const isHeadingsButtonElement = (element: HTMLElement): element is HeadingsButton & HTMLElement =>
    element.tagName === TAG.toUpperCase();

export const createHeadingButton = (
    section: ReactiveStoredArtidocSection | undefined,
): HeadingsButton & HTMLElement => {
    const button = document.createElement(TAG);
    if (!isHeadingsButtonElement(button)) {
        throw new Error("Unable to create the headings button");
    }

    if (section !== undefined) {
        button.section = section.value;
    }

    return button;
};
