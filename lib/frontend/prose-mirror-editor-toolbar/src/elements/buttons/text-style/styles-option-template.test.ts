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

import { describe, it, expect, beforeEach } from "vitest";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import type { HostElement } from "./text-style";
import { renderStylesOption } from "./styles-option-template";

describe("styles-option-template", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getOption = (host: HostElement): HTMLOptionElement => {
        renderStylesOption(host)(host, target);

        const option = target.querySelector("option");
        if (!option) {
            throw new Error("Expected an option");
        }
        return option;
    };

    it("When nothing is activated, then it should render a selected and disabled option", () => {
        const host = {
            is_plain_text_activated: false,
            is_preformatted_text_activated: false,
            current_heading: null,
        } as unknown as HostElement;

        const option = getOption(host);

        expect(option.selected).toBe(true);
        expect(option.disabled).toBe(true);
    });

    it("When plain text is activated, then the option should be disabled and not be selected", () => {
        const host = {
            is_plain_text_activated: true,
            is_preformatted_text_activated: false,
            current_heading: null,
        } as unknown as HostElement;

        renderStylesOption(host)(host, target);

        const option = getOption(host);

        expect(option.selected).toBe(false);
        expect(option.disabled).toBe(true);
    });

    it("When preformatted text is activated, then the option should be disabled and not be selected", () => {
        const host = {
            is_plain_text_activated: false,
            is_preformatted_text_activated: true,
            current_heading: null,
        } as unknown as HostElement;

        renderStylesOption(host)(host, target);

        const option = getOption(host);

        expect(option.selected).toBe(false);
        expect(option.disabled).toBe(true);
    });

    it("When a heading is activated, then the option should be disabled and not be selected", () => {
        const host = {
            is_plain_text_activated: false,
            is_preformatted_text_activated: false,
            current_heading: { level: 2 },
        } as unknown as HostElement;

        const option = getOption(host);

        expect(option.selected).toBe(false);
        expect(option.disabled).toBe(true);
    });
});
