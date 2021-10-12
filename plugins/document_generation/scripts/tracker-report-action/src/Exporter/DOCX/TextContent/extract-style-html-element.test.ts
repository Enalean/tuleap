/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { extractInlineStyles } from "./extract-style-html-element";

describe("extract-style-html-element", () => {
    it("does nothing when no specific styles are set to the element", () => {
        const element = {
            style: {
                color: "",
            },
        } as HTMLElement;

        const initial_style = {};
        const style = extractInlineStyles(element, initial_style);

        expect(style).toBe(initial_style);
    });

    it("transforms color", () => {
        const element = {
            style: {
                color: "rgb(255, 0, 0)",
            },
        } as HTMLElement;

        const style = extractInlineStyles(element, { bold: true });

        expect(style).toStrictEqual({ bold: true, color: "ff0000" });
    });

    it("transforms color without taking transparency into account", () => {
        const element = {
            style: {
                color: "rgba(255, 0, 0, .8)",
            },
        } as HTMLElement;

        const style = extractInlineStyles(element, { bold: true });

        expect(style).toStrictEqual({ bold: true, color: "ff0000" });
    });
});
