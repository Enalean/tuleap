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

import { GetText } from "../../../../../src/scripts/tuleap/gettext/gettext-init";
import {
    buildDeletionDescriptionCallback,
    replaceTrackerIDCallback,
} from "./replacers-modal-delete";

describe("replacers-modal-delete", () => {
    let doc: Document;
    let gettext_provider: GetText;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        gettext_provider = {
            gettext: (str: string) => str,
        } as GetText;
    });

    describe("replaceTrackerIDCallback", () => {
        it("throws when the clicked trash element does not have a data-tracker-id attribute", () => {
            const trash_element = createAndAppendTrashButtonElement(doc);

            expect(() => replaceTrackerIDCallback(trash_element)).toThrow(
                "Missing data-tracker-id attribute on button"
            );
        });

        it(`sets hidden input value from the clicked button's data-app-id value`, () => {
            const delete_button = createAndAppendTrashButtonElement(doc);
            delete_button.dataset.trackerId = "1";

            expect(replaceTrackerIDCallback(delete_button)).toEqual("1");
        });
    });

    describe("buildDeletionDescriptionCallback", () => {
        it(`throws when the clicked trash element does not have a data-tracker-name attribute`, () => {
            const button = createAndAppendTrashButtonElement(doc);
            const callback = buildDeletionDescriptionCallback(gettext_provider);
            expect(() => callback(button)).toThrow("Missing data-tracker-name attribute on button");
        });

        it(`fills the translation placeholder with the clicked trash element's data-tracker-name value`, () => {
            const button = createAndAppendTrashButtonElement(doc);
            button.dataset.trackerName = "Bugs";

            const callback = buildDeletionDescriptionCallback(gettext_provider);
            expect(callback(button)).toContain("Bugs");
        });
    });
});

function createAndAppendTrashButtonElement(doc: Document): HTMLElement {
    const button = doc.createElement("span");
    doc.body.append(button);
    return button;
}
