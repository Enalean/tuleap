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
import { buildTrigger, getFooter, getHeader } from "./common-builder";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
const doc = createLocalDocument();

describe("common popover builder", () => {
    it("it builds header", () => {
        const header = getHeader(doc, "title-id", "my popover");
        expect(header.innerHTML).toMatchInlineSnapshot(
            `<h1 id="title-id" class="tlp-popover-title">my popover</h1>`,
        );
    });
    it("it builds footer", () => {
        const footer = getFooter(doc, "submit-button-id", gettext_provider);
        expect(footer.innerHTML).toMatchInlineSnapshot(
            `<button type="button" class="tlp-button-primary tlp-button-outline tlp-button-small" data-dismiss="popover"></button> <button type="submit" class="tlp-button-primary tlp-button-small" id="submit-button-id"></button>`,
        );
    });
    it("it builds trigger", () => {
        const trigger = buildTrigger(doc, "popover-id", "fa-add");
        expect(trigger.outerHTML).toMatchInlineSnapshot(
            `<i class="fa-solid fa-add ProseMirror-icon" id="trigger-popover-popover-id"></i>`,
        );
    });
});
