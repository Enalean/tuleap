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

import DOMPurify from "dompurify";
import type { PurifyHTML } from "./PurifyHTML";

/**
 * Cache because DOMPurify sanitizing is expensive
 */
export const CachedPurifier = (): PurifyHTML => {
    const purifier = DOMPurify();
    let cached_fragment: DocumentFragment | undefined;

    return {
        sanitize(html_string: string): DocumentFragment {
            if (cached_fragment === undefined) {
                cached_fragment = purifier.sanitize(html_string, {
                    RETURN_DOM_FRAGMENT: true,
                    ALLOWED_TAGS: ["a"],
                });
            }
            return cached_fragment;
        },

        invalidate(): void {
            cached_fragment = undefined;
        },
    };
};
