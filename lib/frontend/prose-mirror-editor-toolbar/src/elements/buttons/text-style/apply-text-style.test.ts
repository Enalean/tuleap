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
import type { MockInstance } from "vitest";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { applyTextStyle } from "./apply-text-style";
import type { InternalTextStyleItem } from "./text-style";
import { OPTION_PLAIN_TEXT } from "./plain-text-option-template";
import { OPTION_PREFORMATTED } from "./preformatted-text-option-template";
import { OPTION_HEADING_1, OPTION_HEADING_2, OPTION_HEADING_3 } from "./heading-option-template";
import { OPTION_SUBTITLE } from "./subtitle-option-template";

describe("apply-text-style", () => {
    let toolbar_bus: ToolbarBus;

    beforeEach(() => {
        toolbar_bus = buildToolbarBus();
    });

    describe("plain-text", () => {
        let applyPlainText: MockInstance;

        beforeEach(() => {
            applyPlainText = vi.spyOn(toolbar_bus, "plainText");
        });

        it("When the option is selected, then it should call toolbar_bus.plainText()", () => {
            const host = {
                is_plain_text_activated: false,
                toolbar_bus,
            } as InternalTextStyleItem;

            applyTextStyle(host, OPTION_PLAIN_TEXT);

            expect(applyPlainText).toHaveBeenCalledOnce();
        });

        it("When the option is selected, then it should NOT call toolbar_bus.plainText() if the option was already selected", () => {
            const host = {
                is_plain_text_activated: true,
                toolbar_bus,
            } as InternalTextStyleItem;

            applyTextStyle(host, OPTION_PLAIN_TEXT);

            expect(applyPlainText).not.toHaveBeenCalled();
        });
    });

    describe("preformatted-text", () => {
        let applyPreformattedText: MockInstance;

        beforeEach(() => {
            applyPreformattedText = vi.spyOn(toolbar_bus, "preformattedText");
        });

        it("When the option is selected, then it should call toolbar_bus.preformattedText()", () => {
            const host = {
                is_preformatted_text_activated: false,
                toolbar_bus,
            } as InternalTextStyleItem;

            applyTextStyle(host, OPTION_PREFORMATTED);

            expect(applyPreformattedText).toHaveBeenCalledOnce();
        });

        it("When the option is selected, then it should NOT call toolbar_bus.preformattedText() if the option was already selected", () => {
            const host = {
                is_preformatted_text_activated: true,
                toolbar_bus,
            } as InternalTextStyleItem;

            applyTextStyle(host, OPTION_PREFORMATTED);

            expect(applyPreformattedText).not.toHaveBeenCalledOnce();
        });
    });

    describe("subtitle", () => {
        let applySubtitle: MockInstance;

        beforeEach(() => {
            applySubtitle = vi.spyOn(toolbar_bus, "subtitle");
        });

        it("When the option is selected, then it should call toolbar_bus.applySubtitle()", () => {
            const host = {
                is_subtitle_activated: false,
                toolbar_bus,
            } as InternalTextStyleItem;

            applyTextStyle(host, OPTION_SUBTITLE);

            expect(applySubtitle).toHaveBeenCalledOnce();
        });

        it("When the option is selected, then it should NOT call toolbar_bus.applySubtitle() if the option was already selected", () => {
            const host = {
                is_subtitle_activated: true,
                toolbar_bus,
            } as InternalTextStyleItem;

            applyTextStyle(host, OPTION_SUBTITLE);

            expect(applySubtitle).not.toHaveBeenCalledOnce();
        });
    });

    describe("headings", () => {
        let applyHeading: MockInstance;

        beforeEach(() => {
            applyHeading = vi.spyOn(toolbar_bus, "heading");
        });

        it.each([
            [OPTION_HEADING_1, 1],
            [OPTION_HEADING_2, 2],
            [OPTION_HEADING_3, 3],
        ])(
            "When the option %s is selected, then it should call toolbar_bus.heading() with the current heading level",
            (selected_option, heading_level) => {
                const host = {
                    current_heading: null,
                    toolbar_bus,
                } as InternalTextStyleItem;

                applyTextStyle(host, selected_option);

                expect(applyHeading).toHaveBeenCalledOnce();
                expect(applyHeading).toHaveBeenCalledWith({ level: heading_level });
            },
        );

        it.each([
            [OPTION_HEADING_1, 1],
            [OPTION_HEADING_2, 2],
            [OPTION_HEADING_3, 3],
        ])(
            "When the option %s is selected, then it should NOT call toolbar_bus.heading() if the option was already selected",
            (selected_option, heading_level) => {
                const host = {
                    current_heading: { level: heading_level },
                    toolbar_bus,
                } as InternalTextStyleItem;

                applyTextStyle(host, selected_option);

                expect(applyHeading).not.toHaveBeenCalled();
            },
        );
    });
});
