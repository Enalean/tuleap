/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { createLocalDocument } from "../../../helpers";
import { renderLinkPopover } from "./LinkPopoverTemplate";
import type { HostElement } from "./LinkPopoverElement";

describe("LinkPopoverTemplate", () => {
    let target: ShadowRoot, is_in_edition_mode: boolean;

    beforeEach(() => {
        const doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getHost = (): HostElement =>
        ({
            is_in_edition_mode,
            buttons_renderer: {
                render: vi.fn(),
            },
            edition_form_renderer: {
                render: vi.fn(),
            },
        }) as unknown as HostElement;

    it("When the tooltip is not in edition mode, then it should display the buttons", () => {
        is_in_edition_mode = false;
        const host = getHost();

        renderLinkPopover(host)(host, target);

        expect(host.buttons_renderer.render).toHaveBeenCalledOnce();
        expect(host.edition_form_renderer.render).not.toHaveBeenCalled();
    });

    it("When the tooltip is in edition mode, then it should display the form", () => {
        is_in_edition_mode = true;
        const host = getHost();

        renderLinkPopover(host)(host, target);

        expect(host.buttons_renderer.render).not.toHaveBeenCalled();
        expect(host.edition_form_renderer.render).toHaveBeenCalledOnce();
    });
});
