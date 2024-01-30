/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { LazyboxItem } from "../GroupCollection";
import type { HostElement } from "./SelectionElement";
import { buildClear } from "./SelectionElement";
import { getClearSelectionButton } from "./ClearSelectionTemplate";
import { LazyboxItemStub } from "../../tests/stubs/LazyboxItemStub";

const noopOnSelection = (item: LazyboxItem | null): void => {
    if (item) {
        //Do nothing
    }
};

describe("ClearSelectionTemplate", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    const getHost = (): HostElement => {
        const host = doc.createElement("span") as HostElement;
        Object.assign(host, {
            multiple: false,
            selected_items: [LazyboxItemStub.withDefaults()],
            clearSelection: buildClear(host),
            onSelection: noopOnSelection,
        });
        return host;
    };

    const getButton = (host: HostElement): HTMLButtonElement => {
        const render = getClearSelectionButton();
        const target = doc.createElement("div") as unknown as ShadowRoot;
        render(host, target);
        return selectOrThrow(
            target,
            "[data-test=clear-current-selection-button]",
            HTMLButtonElement,
        );
    };

    it(`When I click on the button, it will dispatch a "clear-selection" event
        and an "open-dropdown" event
        and will call onSelection() with an empty array parameter`, () => {
        const host = getHost();
        const dispatch = vi.spyOn(host, "dispatchEvent");
        const onSelection = vi.spyOn(host, "onSelection");

        const button = getButton(host);
        button.click();

        expect(dispatch.mock.calls[0][0].type).toBe("clear-selection");
        expect(dispatch.mock.calls[1][0].type).toBe("open-dropdown");
        expect(host.selected_items).toHaveLength(0);
        expect(onSelection).toHaveBeenCalledWith([]);
    });
});
