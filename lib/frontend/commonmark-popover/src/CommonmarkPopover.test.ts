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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { SpyInstance } from "vitest";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import type { InternalCommonmarkPopover } from "./CommonmarkPopover";
import { TAG } from "./CommonmarkPopover";
import { CommonmarkPopoverController } from "./CommonmarkPopoverController";

const isCommonmarkPopover = (
    element: HTMLElement,
): element is HTMLElement & InternalCommonmarkPopover => {
    return true;
};

const noop = (): void => {
    // Do nothing
};

vi.mock("@tuleap/tlp-popovers");

describe("CommonmarkPopover", () => {
    let doc: Document, initPopover: SpyInstance, destroyPopover: SpyInstance;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        vi.useFakeTimers();
        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({
            destroy: noop,
            hide: noop,
        } as Popover);
    });

    const getBuiltCommonmarkPopover = (): HTMLElement & InternalCommonmarkPopover => {
        const commonmark_popover = document.createElement(TAG);
        if (!isCommonmarkPopover(commonmark_popover)) {
            throw new Error("");
        }
        const controller = CommonmarkPopoverController();

        initPopover = vi.spyOn(controller, "initPopover");
        destroyPopover = vi.spyOn(controller, "destroyPopover");

        commonmark_popover.controller = controller;

        return commonmark_popover;
    };

    it("When the popover element is added to the DOM tree, then its controller should init the popover", async () => {
        const commonmark_popover = getBuiltCommonmarkPopover();

        await doc.body.append(commonmark_popover);
        vi.advanceTimersToNextTimer();

        expect(initPopover).toHaveBeenCalledOnce();
    });

    it("When the popover element is removed to the DOM tree, then its controller should destroy the popover", async () => {
        const commonmark_popover = getBuiltCommonmarkPopover();

        await doc.body.append(commonmark_popover);
        vi.advanceTimersToNextTimer();
        await doc.body.removeChild(commonmark_popover);

        expect(destroyPopover).toHaveBeenCalledOnce();
    });
});
