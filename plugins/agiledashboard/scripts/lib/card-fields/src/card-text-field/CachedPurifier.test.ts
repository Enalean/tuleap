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

import { CachedPurifier } from "./CachedPurifier";

interface DOMPurifyInterface {
    sanitize(html_string: string, config: Record<string, unknown>): DocumentFragment;
}

jest.mock("dompurify", () => ({
    default: (): DOMPurifyInterface => ({
        sanitize(html_string): DocumentFragment {
            const doc = document.implementation.createHTMLDocument();
            const template = doc.createElement("template");
            template.innerHTML = html_string;
            return template.content;
        },
    }),
}));

describe(`CachedPurifier`, () => {
    const HTML_STRING = `<span>Span</span>`;

    it(`caches calls to sanitize`, () => {
        const purifier = CachedPurifier();
        const first_fragment = purifier.sanitize(HTML_STRING);
        const second_fragment = purifier.sanitize(HTML_STRING);
        expect(second_fragment).toBe(first_fragment);
    });

    it(`can invalidate its cache`, () => {
        const purifier = CachedPurifier();
        const first_fragment = purifier.sanitize(HTML_STRING);

        purifier.invalidate();
        const second_fragment = purifier.sanitize(`<span>Another span</span>`);
        expect(second_fragment).not.toBe(first_fragment);
        expect(second_fragment.textContent).toBe("Another span");
    });
});
