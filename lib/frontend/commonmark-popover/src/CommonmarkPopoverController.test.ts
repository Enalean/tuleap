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
import type { ControlCommonmarkPopover } from "./CommonmarkPopoverController";
import { CommonmarkPopoverController } from "./CommonmarkPopoverController";
import type { HostElement } from "./CommonmarkPopover";

vi.mock("@tuleap/tlp-popovers");

describe("CommonmarkPopoverController", () => {
    let doc: Document, controller: ControlCommonmarkPopover, host: HostElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        controller = CommonmarkPopoverController();
        host = {
            popover_trigger_element: doc.createElement("button"),
            popover_anchor_element: doc.createElement("i"),
            popover_content_element: doc.createElement("div"),
            is_open: false,
        } as unknown as HostElement;
    });

    describe("Popover instance management", () => {
        let createPopover: SpyInstance, destroyPopoverInstance: SpyInstance;

        beforeEach(() => {
            destroyPopoverInstance = vi.fn();
            createPopover = vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({
                destroy: destroyPopoverInstance,
            } as unknown as Popover);
        });

        it("initPopover() should create a Popover instance", () => {
            controller.initPopover(host);

            expect(createPopover).toHaveBeenCalledOnce();
            expect(createPopover).toHaveBeenCalledWith(
                host.popover_trigger_element,
                host.popover_content_element,
                {
                    anchor: host.popover_anchor_element,
                    placement: "right-start",
                    trigger: "click",
                },
            );
        });

        it("initPopover() should not create a Popover instance when one has already been created", () => {
            controller.initPopover(host);
            controller.initPopover(host);

            expect(createPopover).toHaveBeenCalledOnce();
        });

        it("destroyPopover() should destroy the current Popover instance", () => {
            controller.initPopover(host);
            controller.destroyPopover();

            expect(destroyPopoverInstance).toHaveBeenCalledOnce();
        });
    });

    it(`onPopoverShown() should set the component's is_open attribute to true`, () => {
        host.is_open = false;
        controller.onPopoverShown(host);

        expect(host.is_open).toBe(true);
    });

    it(`onPopoverHidden() should set the component's is_open attribute to false`, () => {
        host.is_open = true;
        controller.onPopoverHidden(host);

        expect(host.is_open).toBe(false);
    });
});
