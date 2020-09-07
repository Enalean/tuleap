/*
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

import { closeExistingModal } from "./close-existing-modal";

describe("closeExistingModal", () => {
    it("Simulates a click on the backdrop to close the TLP modal", () => {
        const doc = document.implementation.createHTMLDocument();
        const div = doc.createElement("div");
        div.id = "tlp-modal-backdrop";
        doc.body.appendChild(div);

        const click = jest.spyOn(div, "click");

        closeExistingModal(doc);

        expect(click).toHaveBeenCalled();
    });

    it("Simulates a click on the backdrop to close the bootstrap modal", () => {
        const doc = document.implementation.createHTMLDocument();
        const div = doc.createElement("div");
        div.classList.add("modal-backdrop");
        doc.body.appendChild(div);

        const click = jest.spyOn(div, "click");

        closeExistingModal(doc);

        expect(click).toHaveBeenCalled();
    });
});
