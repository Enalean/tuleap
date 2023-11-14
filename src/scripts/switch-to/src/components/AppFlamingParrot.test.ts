/*
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

import { afterEach, beforeEach, describe, expect, it, jest } from "@jest/globals";
import AppFlamingParrot from "./AppFlamingParrot.vue";
import { useRootStore } from "../stores/root";
import { getGlobalTestOptions } from "../helpers/global-options-for-test";
import { cleanup, configure, fireEvent, render } from "@testing-library/vue";

describe("AppFlamingParrot", () => {
    beforeEach(() => {
        configure({
            testIdAttribute: "data-test",
        });
    });

    afterEach(() => {
        cleanup();
    });

    it("Autofocus the first input in the modal", async () => {
        const { getByTestId } = render(AppFlamingParrot, {
            global: getGlobalTestOptions(),
        });

        const input = getByTestId("switch-to-filter");
        if (!(input instanceof HTMLElement)) {
            throw Error("input not found");
        }
        const focus = jest.spyOn(input, "focus");

        await fireEvent(getByTestId("switch-to-modal"), new CustomEvent("shown"));

        expect(focus).toHaveBeenCalled();
    });

    it("Loads the history when the modal is shown", async () => {
        const { getByTestId } = render(AppFlamingParrot, {
            global: getGlobalTestOptions(),
        });

        await fireEvent(getByTestId("switch-to-modal"), new CustomEvent("shown"));

        expect(useRootStore().loadHistory).toHaveBeenCalledTimes(1);
    });

    it("Clears the filter value when modal is closed", async () => {
        const { getByTestId } = render(AppFlamingParrot, {
            global: getGlobalTestOptions(),
        });

        await fireEvent(getByTestId("switch-to-modal"), new CustomEvent("hidden"));

        expect(useRootStore().updateFilterValue).toHaveBeenCalledWith("");
    });
});
