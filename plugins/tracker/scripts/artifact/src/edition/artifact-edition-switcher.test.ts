/*
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

import { noFieldIsSwitchedToEdit } from "./artifact-edition-switcher";

describe("noFieldIsSwitchedToEdit", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("should return true when no fields are in edition", () => {
        expect(noFieldIsSwitchedToEdit(doc)).toBe(true);
    });

    it("should return false when there are fields in edition", () => {
        const field = doc.createElement("div");
        field.className = "tracker_artifact_field in-edition";
        doc.body.appendChild(field);

        expect(noFieldIsSwitchedToEdit(doc)).toBe(false);
    });
});
