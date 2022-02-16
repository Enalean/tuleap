/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

export interface Highlight {
    // It should not be compatible with Background so that we can distinguish them
    _type: "Highlight";
    content: string;
}

/**
 * Text that is not highlighted
 */
export interface Background {
    // It should not be compatible with Highlight so that we can distinguish them
    _type: "Background";
    content: string;
}

export type HighlightedText = Highlight | Background;

export const HighlightedText = {
    highlight: (content: string): Highlight => ({ _type: "Highlight", content }),

    background: (content: string): Background => ({ _type: "Background", content }),

    isHighlight: (text: Highlight | Background): text is Highlight => text._type === "Highlight",
};
