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

import { describe, it, expect, beforeEach, vi, beforeAll } from "vitest";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import { EVENT_TLP_POPOVER_HIDDEN } from "@tuleap/tlp-popovers";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import type { PopoverHost } from "./connect-popover";
import { connectPopover } from "./connect-popover";

vi.mock("@tuleap/tlp-popovers");

describe("connect-popover", () => {
    let doc: Document;

    beforeAll(() => {
        // Good enough mock for the tests
        global.ResizeObserver = class ResizeObserver {
            observe(): void {
                // do nothing
            }
            unobserve(): void {
                // do nothing
            }
            disconnect(): void {
                // do nothing
            }
        };
    });

    beforeEach(() => {
        doc = createLocalDocument();
    });

    const getHost = (): PopoverHost => {
        const host_element = doc.createElement("div");
        return Object.assign(host_element, {
            button_element: doc.createElement("button"),
            popover_element: doc.createElement("div"),
            toolbar_bus: buildToolbarBus(),
            render(): HTMLElement {
                return host_element;
            },
        }) as unknown as PopoverHost;
    };

    it("When the component is connected, then it should assign host a new popover instance", () => {
        const host = getHost();
        const popover_instance = {} as Popover;
        const createPopover = vi
            .spyOn(tlp_popovers, "createPopover")
            .mockReturnValue(popover_instance);

        connectPopover(host, doc);

        expect(createPopover).toHaveBeenCalledOnce();
        expect(createPopover).toHaveBeenCalledWith(host.button_element, host.popover_element, {
            placement: "bottom-start",
            trigger: "click",
        });
        expect(host.popover_instance).toBe(popover_instance);
    });

    it("When the component is connected, then it should move its popover element to document.body", () => {
        const host = getHost();
        connectPopover(host, doc);

        expect(host.popover_element.parentElement).toBe(doc.body);
    });

    it("When the component is connected, then it make the popover element listen for EVENT_TLP_POPOVER_HIDDEN so it can give back the focus to the editor", () => {
        const host = getHost();
        const popover_instance = {
            hide() {
                host.popover_element.dispatchEvent(new CustomEvent(EVENT_TLP_POPOVER_HIDDEN));
            },
        } as Popover;

        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue(popover_instance);

        const focusEditor = vi.spyOn(host.toolbar_bus, "focusEditor");
        connectPopover(host, doc);

        popover_instance.hide();

        expect(focusEditor).toHaveBeenCalledOnce();
    });

    it(`When the component is disconnected, then it should:
        - destroy the popover instance
        - remove the EVENT_TLP_POPOVER_HIDDEN listener from the popover element
        - move back the popover element inside itself`, () => {
        const host = getHost();

        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({
            destroy: vi.fn(),
        } as unknown as Popover);

        const removeEventListener = vi.spyOn(host.popover_element, "removeEventListener");

        const disconnect = connectPopover(host, doc);

        disconnect();

        expect(removeEventListener).toHaveBeenCalledOnce();
        expect(removeEventListener).toHaveBeenCalledWith(
            EVENT_TLP_POPOVER_HIDDEN,
            expect.any(Function),
        );
        expect(host.popover_instance.destroy).toHaveBeenCalledOnce();
        expect(host.popover_element.parentElement).toBe(host);
    });
});
