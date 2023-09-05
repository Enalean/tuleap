/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { SpyInstance } from "vitest";
import { expect } from "vitest";
import type { ListPickerItem } from "../type";

export function expectChangeEventToHaveBeenFiredOnSourceSelectBox(
    spy: SpyInstance,
    nb_times: number,
): void {
    if (nb_times === 0) {
        expect(spy).not.toHaveBeenCalled();
        return;
    }

    if (nb_times > 1) {
        expect(spy).toHaveBeenCalledTimes(nb_times);
    }
    const event = spy.mock.calls[0][0];
    expect(event.type).toBe("change");
    expect(event.bubbles).toBe(true);
}

export function expectItemToBeSelected(item: ListPickerItem): void {
    expect(item.is_selected).toBe(true);
    expect(item.element.getAttribute("aria-selected")).toBe("true");
    expect(item.target_option.getAttribute("selected")).toBe("selected");
    expect(item.target_option.selected).toBe(true);
}

export function expectItemNotToBeSelected(item: ListPickerItem): void {
    expect(item.is_selected).toBe(false);
    expect(item.element.getAttribute("aria-selected")).toBe("false");
    expect(item.target_option.hasAttribute("selected")).toBe(false);
    expect(item.target_option.selected).toBe(false);
}
