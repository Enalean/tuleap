/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import { describe, expect, it } from "vitest";
import type { TextField } from "../image/popover-image";
import { createAndInsertField } from "./fields-adder";
import { createLocalDocument } from "../../../helpers";

const doc = createLocalDocument();
describe("fields adder", () => {
    it("it add given fields to container", () => {
        const container = doc.createElement("form");

        const href_field: TextField = {
            placeholder: "https://example.com",
            label: "Label",
            required: true,
            type: "url",
            focus: true,
            id: "id-url",
            name: "URL",
            value: "",
        };
        const title_field: TextField = {
            placeholder: "Whatever",
            label: "Whatever",
            type: "text",
            required: false,
            focus: false,
            id: "id-whatever",
            name: "whatever",
            value: "",
        };

        createAndInsertField([href_field, title_field], container, doc);
        expect(container.innerHTML).toMatchInlineSnapshot(
            `
          <div class="tlp-form-element"><label for="id-url" class="tlp-label">Label<i class="fa-solid fa-asterisk" aria-hidden="true"></i></label><input id="id-url" name="URL" type="url" class="tlp-input" placeholder="https://example.com" required=""></div>
          <div class="tlp-form-element"><label for="id-whatever" class="tlp-label">Whatever</label><input id="id-whatever" name="whatever" type="text" class="tlp-input" placeholder="Whatever"></div>
        `,
        );
    });
});
