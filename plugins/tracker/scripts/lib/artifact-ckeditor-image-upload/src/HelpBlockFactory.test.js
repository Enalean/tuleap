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

import { HelpBlockFactory } from "./HelpBlockFactory";

const createDocument = () => document.implementation.createHTMLDocument();

describe(`HelpBlockFactory`, () => {
    let doc, factory, textarea, help_block_element;
    beforeEach(() => {
        doc = createDocument();
        textarea = doc.createElement("textarea");
        help_block_element = doc.createElement("div");
        doc.body.append(textarea, help_block_element);
        const gettext_provider = {
            gettext: (english) => english,
        };
        factory = new HelpBlockFactory(doc, gettext_provider, textarea);
    });

    describe(`createHelpBlock()`, () => {
        it(`creates a paragraph with some translated text and adds it to the help block element,
            and returns a help_block object that can react to format changes`, () => {
            help_block_element.id = "help-block-id";
            textarea.dataset.helpId = "help-block-id";

            const help_block = factory.createHelpBlock(textarea);

            expect(help_block_element.innerHTML).toBe(
                `<p>You can drag 'n drop or paste image directly in the editor.</p>`,
            );
            expect(help_block).not.toBeNull();
            expect(help_block.onFormatChange).toBeDefined();
        });

        it(`returns null when there is no "data-help-id" on the given textarea`, () => {
            help_block_element.id = "help-block-id";

            const help_block = factory.createHelpBlock(textarea);

            expect(help_block_element.innerHTML).toBe("");
            expect(help_block).toBeNull();
        });

        it(`returns null when the referenced help block can't be found`, () => {
            help_block_element.id = "help-block-id";
            textarea.dataset.helpId = "NOT-help-block-id";

            const help_block = factory.createHelpBlock(textarea);

            expect(help_block_element.innerHTML).toBe("");
            expect(help_block).toBeNull();
        });

        it(`returns null when the help block already has text content`, () => {
            help_block_element.id = "help-block-id";
            textarea.dataset.helpId = "help-block-id";
            help_block_element.textContent = "Some other text";

            const help_block = factory.createHelpBlock(textarea);

            expect(help_block_element.innerHTML).toBe("Some other text");
            expect(help_block).toBeNull();
        });
    });
});
