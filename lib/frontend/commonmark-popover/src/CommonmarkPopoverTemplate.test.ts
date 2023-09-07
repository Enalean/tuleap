/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { selectOrThrow } from "@tuleap/dom";
import { EVENT_TLP_POPOVER_SHOWN, EVENT_TLP_POPOVER_HIDDEN } from "@tuleap/tlp-popovers";
import { GettextProviderStub } from "../tests/stubs/GettextProviderStub";
import type { HostElement } from "./CommonmarkPopover";
import { getPopoverTemplate } from "./CommonmarkPopoverTemplate";
import { CommonmarkPopoverController } from "./CommonmarkPopoverController";

describe("CommonmarkPopoverTemplate", () => {
    let host: HostElement;

    beforeEach(() => {
        host = {
            is_open: true,
            controller: CommonmarkPopoverController(),
        } as HostElement;
    });

    const getRenderedCommonmarkPopover = (): ShadowRoot => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        const render = getPopoverTemplate(host, GettextProviderStub);

        render(host, target);

        return target;
    };

    describe("Trigger state", () => {
        it("The label and the icon should have the common-mark-popover-trigger-active class when the popover is open", () => {
            host.is_open = true;

            const popover = getRenderedCommonmarkPopover();
            const trigger_label = selectOrThrow(popover, "[data-test=popover-trigger-label]");
            const trigger_icon = selectOrThrow(popover, "[data-test=popover-trigger-icon]");

            expect(Array.from(trigger_label.classList)).toContain(
                "common-mark-popover-trigger-active",
            );
            expect(Array.from(trigger_icon.classList)).toContain(
                "common-mark-popover-trigger-active",
            );
        });

        it("The label and the icon should not have the common-mark-popover-trigger-active class when the popover is not open", () => {
            host.is_open = false;

            const popover = getRenderedCommonmarkPopover();
            const trigger_label = selectOrThrow(popover, "[data-test=popover-trigger-label]");
            const trigger_icon = selectOrThrow(popover, "[data-test=popover-trigger-icon]");

            expect(Array.from(trigger_label.classList)).not.toContain(
                "common-mark-popover-trigger-active",
            );
            expect(Array.from(trigger_icon.classList)).not.toContain(
                "common-mark-popover-trigger-active",
            );
        });
    });

    describe("Popover shown/hidden", () => {
        it("When the Popover dispatches a tlp-popover-shown event, then it should set host.is_open to true", () => {
            host.is_open = false;

            const popover = getRenderedCommonmarkPopover();
            const popover_content = selectOrThrow(popover, "[data-test=popover-content]");

            popover_content.dispatchEvent(new CustomEvent(EVENT_TLP_POPOVER_SHOWN));

            expect(host.is_open).toBe(true);
        });

        it("When the Popover dispatches a tlp-popover-hidden event, then it should set host.is_open to false", () => {
            host.is_open = true;

            const popover = getRenderedCommonmarkPopover();
            const popover_content = selectOrThrow(popover, "[data-test=popover-content]");

            popover_content.dispatchEvent(new CustomEvent(EVENT_TLP_POPOVER_HIDDEN));

            expect(host.is_open).toBe(false);
        });
    });
});
