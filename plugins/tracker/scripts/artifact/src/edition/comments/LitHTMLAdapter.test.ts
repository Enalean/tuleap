/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { html } from "lit-html";
import { selectOrThrow } from "@tuleap/dom";
import { LitHTMLAdapter } from "./LitHTMLAdapter";

describe(`LitHTMLAdapter`, () => {
    let mount_point: HTMLElement, render_before: HTMLElement;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        render_before = doc.createElement("p");
        mount_point.append(render_before);
    });

    function render(is_in_edition: boolean): void {
        LitHTMLAdapter().render({
            mount_point,
            render_before,
            is_in_edition,
            edit_zone: html`<div data-test="edit-zone">The edit zone</div>`,
        });
    }

    it(`renders an edit zone before the given reference in the mount point`, () => {
        render(true);
        const found_zone = selectOrThrow(mount_point, "[data-test=edit-zone]");
        expect(found_zone.nextElementSibling).toBe(render_before);
    });

    it(`renders nothing when the comment is not in edition`, () => {
        render(false);
        expect(mount_point.querySelector("[data-test=edit-zone]")).toBeNull();
    });
});
