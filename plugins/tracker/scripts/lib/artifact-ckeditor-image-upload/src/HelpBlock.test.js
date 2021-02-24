/*
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

import { HelpBlock } from "./HelpBlock";

const createDocument = () => document.implementation.createHTMLDocument();

describe(`HelpBlock`, () => {
    let element, help_block;
    beforeEach(() => {
        const doc = createDocument();
        element = doc.createElement("div");
        doc.body.append(element);
        help_block = new HelpBlock(element);
    });

    describe(`onFormatChange()`, () => {
        it(`when the format changes to "html", it will show the help block`, () => {
            help_block.onFormatChange("html");

            expect(element.classList.contains("shown")).toBe(true);
        });

        it(`when the format changes to something else, it will hide the help block`, () => {
            help_block.onFormatChange("text");

            expect(element.classList.contains("shown")).toBe(false);
        });
    });
});
