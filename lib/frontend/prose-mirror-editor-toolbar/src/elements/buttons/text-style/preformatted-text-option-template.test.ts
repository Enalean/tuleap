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
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import { renderPreformattedTextOption } from "./preformatted-text-option-template";
import type { HostElement } from "./text-style";

describe("preformatted-text-option-template", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getPreformattedTextOption = (host: HostElement): HTMLOptionElement => {
        renderPreformattedTextOption(host, gettext_provider)(host, target);

        const option = target.querySelector("option");
        if (!option) {
            throw new Error("Expected an option");
        }
        return option;
    };

    it("When preformatted text is disabled, then it should render nothing", () => {
        const host = { style_elements: { preformatted: false } } as HostElement;
        renderPreformattedTextOption(host, gettext_provider)(host, target);

        const option = target.querySelector("option");
        expect(option).toBeNull();
    });

    it.each([
        [false, "should not be selected"],
        [true, "should be selected"],
    ])(
        "When host.is_preformatted_text_activated === %s then the option %s",
        (is_preformatted_text_activated) => {
            const host = {
                is_preformatted_text_activated,
                style_elements: { preformatted: true },
            } as HostElement;
            const option = getPreformattedTextOption(host);

            expect(option.selected).toBe(is_preformatted_text_activated);
        },
    );

    it("When the option is clicked, then it should call toolbar_bus.preformattedText()", () => {
        const toolbar_bus = buildToolbarBus();
        const host = {
            is_preformatted_text_activated: false,
            style_elements: { preformatted: true },
            toolbar_bus,
        } as HostElement;
        const applyPreformattedText = vi.spyOn(toolbar_bus, "preformattedText");

        getPreformattedTextOption(host).click();

        expect(applyPreformattedText).toHaveBeenCalledOnce();
    });

    it("When the option is clicked, then it should NOT call toolbar_bus.preformattedText() if the option was already selected", () => {
        const toolbar_bus = buildToolbarBus();
        const host = {
            is_preformatted_text_activated: true,
            style_elements: { preformatted: true },
            toolbar_bus,
        } as HostElement;
        const applyPreformattedText = vi.spyOn(toolbar_bus, "preformattedText");

        getPreformattedTextOption(host).click();

        expect(applyPreformattedText).not.toHaveBeenCalledOnce();
    });
});
