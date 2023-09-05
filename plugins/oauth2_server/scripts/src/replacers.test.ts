/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { GetText } from "@tuleap/gettext";
import {
    hiddenInputReplaceCallback,
    buildRevocationReplaceCallback,
    buildDeletionReplaceCallback,
    buildRegenerationReplaceBallback,
} from "./replacers";

describe(`replacers`, () => {
    let doc: Document;
    let gettext_provider: GetText;
    beforeEach(() => {
        doc = createLocalDocument();
        gettext_provider = {
            gettext: (english: string) => english,
        } as GetText;
    });

    describe(`hiddenInputReplaceCallback`, () => {
        it(`throws when the clicked button does not have a data-app-id attribute`, () => {
            const delete_button = createAndAppendButton(doc);

            expect(() => hiddenInputReplaceCallback(delete_button)).toThrow(
                "Missing data-app-id attribute on button",
            );
        });

        it(`sets hidden input value from the clicked button's data-app-id value`, () => {
            const delete_button = createAndAppendButton(doc);
            delete_button.dataset.appId = "123";

            expect(hiddenInputReplaceCallback(delete_button)).toBe("123");
        });
    });

    type replacerFactory = (gettext_provider: GetText) => (clicked_button: HTMLElement) => string;

    describe.each([
        ["buildDeletionReplaceCallback", buildDeletionReplaceCallback],
        ["buildRevocationReplaceCallback", buildRevocationReplaceCallback],
        ["buildRegenerationReplaceBallback", buildRegenerationReplaceBallback],
    ])("%s", (name: string, factory: replacerFactory) => {
        it(`throws when the clicked button does not have a data-app-name attribute`, () => {
            const button = createAndAppendButton(doc);
            const callback = factory(gettext_provider);
            expect(() => callback(button)).toThrow("Missing data-app-name attribute on button");
        });

        it(`fills the translation placeholder with the clicked button's data-app-name value`, () => {
            const button = createAndAppendButton(doc);
            button.dataset.appName = "My OAuth2 App";

            const callback = factory(gettext_provider);
            expect(callback(button)).toContain("My OAuth2 App");
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function createAndAppendButton(doc: Document): HTMLElement {
    const button = doc.createElement("button");
    doc.body.append(button);
    return button;
}
