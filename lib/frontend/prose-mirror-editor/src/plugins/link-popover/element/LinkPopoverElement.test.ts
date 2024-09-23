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
import { createLocalDocument } from "../../../helpers";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import type { HostElement } from "./LinkPopoverElement";
import { connect } from "./LinkPopoverElement";

const noop = (): void => {
    // Do nothing
};

vi.mock("@tuleap/tlp-popovers");

describe("LinkPopoverElement", () => {
    let doc: Document, popover_anchor: HTMLElement;

    beforeEach(() => {
        doc = createLocalDocument();
        popover_anchor = doc.createElement("span");
    });

    const getHost = (): HostElement => {
        return Object.assign(doc.createElement("div"), {
            popover_anchor,
            popover_element: doc.createElement("div") as HTMLElement,
        } as HostElement);
    };

    describe("connect/disconnect", () => {
        it("When the component is connected, then a popover should be created and shown", () => {
            const mocked_popover_instance = {
                show: vi.fn(),
                hide: noop,
                destroy: noop,
            };
            const createPopover = vi.spyOn(tlp_popovers, "createPopover");
            createPopover.mockReturnValue(mocked_popover_instance);

            connect(getHost());

            expect(createPopover).toHaveBeenCalledOnce();
            expect(mocked_popover_instance.show).toHaveBeenCalledOnce();
        });

        it("When the component is disconnected, then it should destroy the popover", () => {
            const mocked_popover_instance = {
                show: noop,
                hide: noop,
                destroy: vi.fn(),
            };
            const createPopover = vi.spyOn(tlp_popovers, "createPopover");
            createPopover.mockReturnValue(mocked_popover_instance);

            const disconnect = connect(getHost());

            disconnect();

            expect(mocked_popover_instance.destroy).toHaveBeenCalledOnce();
        });
    });
});
