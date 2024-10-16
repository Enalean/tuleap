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
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { buildToolbarBus, NB_HEADING } from "@tuleap/prose-mirror-editor";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import type { HostElement } from "./text-style";
import { renderHeadingOption, renderHeadingsOptions } from "./heading-option-template";

describe("heading-option-template", () => {
    let toolbar_bus: ToolbarBus, target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
        toolbar_bus = buildToolbarBus();
    });

    const getOptionElement = (host: HostElement, level: number): HTMLOptionElement => {
        renderHeadingOption(host, level)(host, target);
        const option = target.querySelector("option");
        if (!option) {
            throw new Error("Expected an option");
        }

        return option;
    };

    it("When the heading level is the one being applied, then the option should be selected and NOT disabled", () => {
        const current_level = 2;
        const host = {
            toolbar_bus,
            current_heading: { level: current_level },
        } as HostElement;

        const option = getOptionElement(host, current_level);

        expect(option.disabled).toBe(false);
        expect(option.selected).toBe(true);
    });

    it("When the heading level is NOT the one being applied, then the option should NOT be selected and disabled", () => {
        const heading_level = 1;
        const host = {
            toolbar_bus,
            current_heading: { level: heading_level + 1 },
        } as HostElement;

        const option = getOptionElement(host, heading_level);

        expect(option.disabled).toBe(false);
        expect(option.selected).toBe(false);
    });

    it("When clicked, toolbar_bus.heading() should be called with the option value", () => {
        const heading_level = 3;
        const host = {
            toolbar_bus,
            current_heading: { level: 2 },
        } as HostElement;

        const applyHeading = vi.spyOn(toolbar_bus, "heading");
        getOptionElement(host, heading_level).click();

        expect(applyHeading).toHaveBeenCalledOnce();
        expect(applyHeading).toHaveBeenCalledWith({ level: heading_level });
    });

    it("When clicked, toolbar_bus.heading() should NOT be called with the option value if the heading level didn't change", () => {
        const heading_level = 3;
        const host = {
            toolbar_bus,
            current_heading: { level: 3 },
        } as HostElement;

        const applyHeading = vi.spyOn(toolbar_bus, "heading");
        getOptionElement(host, heading_level).click();

        expect(applyHeading).not.toHaveBeenCalled();
    });

    describe("renderHeadingsOptions()", () => {
        it("should render nothing when headings are disabled", () => {
            const host = {
                style_elements: { headings: false },
            } as HostElement;

            renderHeadingsOptions(host)(host, target);
            const options = target.querySelectorAll("option");

            expect(options.length).toBe(0);
        });

        it("should render heading options when headings are enabled", () => {
            const host = {
                style_elements: { headings: true },
                current_heading: null,
            } as HostElement;

            renderHeadingsOptions(host)(host, target);
            const options = target.querySelectorAll("option");

            expect(options.length).toBe(NB_HEADING);
        });
    });
});
