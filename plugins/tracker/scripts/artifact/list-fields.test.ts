/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import {
    initListPickersInArtifactCreationView,
    initListPickersPostUpdateErrorView,
    listenToggleEditionEvents,
} from "./list-fields";

describe("list-fields", () => {
    let doc: HTMLDocument;

    function createArtifactFormElementFieldInReadModeOfType(
        type: string
    ): {
        button: Element;
        select: HTMLSelectElement;
    } {
        const field = document.createElement("div");
        field.setAttribute("class", `tracker_artifact_field-${type}`);

        const button = document.createElement("button");
        button.setAttribute("class", "tracker_formelement_edit");

        const hidden_edition_field = document.createElement("div");
        hidden_edition_field.setAttribute("class", "tracker_hidden_edition_field");

        const select = document.createElement("select");

        if (type === "msb") {
            select.setAttribute("multiple", "multiple");
        }

        hidden_edition_field.appendChild(select);
        field.appendChild(button);
        field.appendChild(hidden_edition_field);
        doc.body.appendChild(field);

        return {
            button,
            select,
        };
    }

    function createArtifactFormElementFieldInEditionModeOfType(
        type: string,
        is_in_edition_mode = false
    ): HTMLSelectElement {
        const field = document.createElement("div");
        field.setAttribute("class", `tracker_artifact_field-${type}`);

        if (is_in_edition_mode) {
            field.classList.add("in-edition");
        }

        const select = document.createElement("select");

        if (type === "msb") {
            select.setAttribute("multiple", "multiple");
        }

        field.appendChild(select);
        doc.body.appendChild(field);

        return select;
    }

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("should listen for clicks on fields labels to create a list picker when the <select> is shown", () => {
        ["sb", "msb"].forEach((type) => {
            const { button, select } = createArtifactFormElementFieldInReadModeOfType(type);

            listenToggleEditionEvents(doc);

            expect(select.classList).not.toContain("list-picker-hidden-accessible");
            button.dispatchEvent(new Event("click"));
            expect(select.classList).toContain("list-picker-hidden-accessible");
        });
    });

    it("should init list-pickers when the artifact view is in creation mode", () => {
        ["sb", "msb"].forEach((type) => {
            const select = createArtifactFormElementFieldInEditionModeOfType(type);

            expect(select.classList).not.toContain("list-picker-hidden-accessible");
            initListPickersInArtifactCreationView(doc);
            expect(select.classList).toContain("list-picker-hidden-accessible");
        });
    });

    it("should init list-pickers when list fields are in edition mode", () => {
        ["sb", "msb"].forEach((type) => {
            const select = createArtifactFormElementFieldInEditionModeOfType(type, true);

            expect(select.classList).not.toContain("list-picker-hidden-accessible");
            initListPickersPostUpdateErrorView(doc);
            expect(select.classList).toContain("list-picker-hidden-accessible");
        });
    });
});
