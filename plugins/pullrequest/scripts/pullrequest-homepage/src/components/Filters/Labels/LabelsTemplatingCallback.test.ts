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

import { html } from "hybrids";
import { describe, it, expect } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { LazyboxItem } from "@tuleap/lazybox";
import { ProjectLabelStub } from "../../../../tests/stubs/ProjectLabelStub";
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { LabelsTemplatingCallback } from "./LabelsTemplatingCallback";

const renderTemplate = (item: LazyboxItem): HTMLElement => {
    const doc = document.implementation.createHTMLDocument();
    const target = doc.createElement("span");
    const template = LabelsTemplatingCallback(GettextStub)(html, item);

    template(target, target);

    return target;
};

describe("LabelsTemplatingCallback", () => {
    it("Given a LazyboxItem containing an outlined label, then it should display it colored and outlined", () => {
        const label = ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency");
        const rendered_template = renderTemplate({
            is_disabled: false,
            value: label,
        });

        const displayed_label = selectOrThrow(rendered_template, "[data-test=pull-request-label]");

        expect(displayed_label.textContent?.trim()).toBe(label.label);
        expect(displayed_label.classList.contains("tlp-badge-outline")).toBe(true);
        expect(displayed_label.classList.contains(`tlp-badge-${label.color}`)).toBe(true);
    });

    it("Given a LazyboxItem containing a NOT outlined label, then it should display it colored and NOT outlined", () => {
        const label = ProjectLabelStub.regulardWithIdAndLabel(1, "Emergency");
        const rendered_template = renderTemplate({
            is_disabled: false,
            value: label,
        });

        const displayed_label = selectOrThrow(rendered_template, "[data-test=pull-request-label]");

        expect(displayed_label.textContent?.trim()).toBe(label.label);
        expect(displayed_label.classList.contains("tlp-badge-outline")).toBe(false);
        expect(displayed_label.classList.contains(`tlp-badge-${label.color}`)).toBe(true);
    });

    it("Given a disabled Lazybox item, then it should display it disabled", () => {
        const label = ProjectLabelStub.regulardWithIdAndLabel(1, "Emergency");
        const rendered_template = renderTemplate({
            is_disabled: true,
            value: label,
        });

        const displayed_label = selectOrThrow(rendered_template, "[data-test=pull-request-label]");

        expect(displayed_label.textContent?.trim()).toBe(label.label);
        expect(displayed_label.classList.contains("tlp-badge-outline")).toBe(false);
        expect(
            displayed_label.classList.contains("pull-request-autocompleter-badge-disabled"),
        ).toBe(true);
        expect(displayed_label.classList.contains(`tlp-badge-${label.color}`)).toBe(true);
    });

    it("Given a LazyboxItem which does not contain a label, then it should display nothing", () => {
        const rendered_template = renderTemplate({
            is_disabled: false,
            value: {},
        });

        expect(rendered_template.childElementCount).toBe(0);
    });
});
