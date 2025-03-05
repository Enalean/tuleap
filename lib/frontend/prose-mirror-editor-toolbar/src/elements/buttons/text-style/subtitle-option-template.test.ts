/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { renderSubtitleOption } from "./subtitle-option-template";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import type { HostElement } from "./text-style";

describe("subtitle-option-template", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getSubtitleOption = (host: HostElement): HTMLOptionElement => {
        renderSubtitleOption(host, gettext_provider)(host, target);

        const option = target.querySelector("option");
        if (!option) {
            throw new Error("Expected an option");
        }
        return option;
    };

    it("When preformatted text is disabled, then it should render nothing", () => {
        const host = { style_elements: { subtitles: false } } as HostElement;
        renderSubtitleOption(host, gettext_provider)(host, target);

        const option = target.querySelector("option");
        expect(option).toBeNull();
    });

    it.each([
        [false, "should not be selected"],
        [true, "should be selected"],
    ])("When host.is_subtitle_activated === %s then the option %s", (is_subtitle_activated) => {
        const host = {
            is_subtitle_activated,
            style_elements: { subtitles: true },
        } as HostElement;
        const option = getSubtitleOption(host);

        expect(option.selected).toBe(is_subtitle_activated);
    });
});
