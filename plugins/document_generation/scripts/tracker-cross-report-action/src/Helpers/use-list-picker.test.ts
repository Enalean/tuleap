/**
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

import { describe, vi, it, expect } from "vitest";
import * as list_picker from "@tuleap/list-picker";
import { useListPicker } from "./use-list-picker";
import { effectScope, ref } from "vue";

vi.mock("@tuleap/list-picker");

describe("useListPicker", (): void => {
    it("creates list picker", () => {
        const create_spy = vi.spyOn(list_picker, "createListPicker").mockReturnValue({
            destroy: () => {
                // Noop
            },
        });

        const target = document.createElement("select");

        const scope = effectScope();

        scope.run(() => {
            useListPicker(ref(target), {});
        });

        expect(create_spy).toHaveBeenCalled();

        scope.stop();
    });

    it("cleans up list picker", () => {
        const destroy_spy = vi.fn();
        vi.spyOn(list_picker, "createListPicker").mockReturnValue({
            destroy: destroy_spy,
        });

        const target = document.createElement("select");

        const scope = effectScope();

        scope.run(() => {
            useListPicker(ref(target), {});
        });

        scope.stop();

        expect(destroy_spy).toHaveBeenCalled();
    });

    it("does nothing when target does not exist", () => {
        const create_spy = vi.spyOn(list_picker, "createListPicker");

        const scope = effectScope();

        scope.run(() => {
            useListPicker(ref(undefined), {});
        });

        scope.stop();

        expect(create_spy).not.toHaveBeenCalled();
    });
});
