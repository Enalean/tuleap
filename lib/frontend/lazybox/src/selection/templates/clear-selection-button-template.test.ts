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

import { describe, it, expect, vi } from "vitest";
import { buildClearSelectionButtonElement } from "./clear-selection-button-template";

describe("clear-selection-button-template", () => {
    it("should return a button which executes the given callback when it is clicked", () => {
        const clear_selection_callback = vi.fn();
        const button = buildClearSelectionButtonElement(clear_selection_callback);

        button.dispatchEvent(new Event("pointerup"));

        expect(clear_selection_callback).toHaveBeenCalledOnce();
    });
});
