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

import { ref } from "vue";
import { describe, beforeEach, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { DOMWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../tests/global-options-for-tests";
import { ListFiltersStore } from "../ListFiltersStore";
import type { StoreListFilters } from "../ListFiltersStore";
import KeywordsSearchInput from "./KeywordsSearchInput.vue";
import { KeywordFilterBuilder } from "./KeywordFilter";
import { GettextStub } from "../../../../tests/stubs/GettextStub";

describe("KeywordsSearchInput", () => {
    let filters_store: StoreListFilters;

    beforeEach(() => {
        filters_store = ListFiltersStore(ref([]));
    });

    const getInput = (): DOMWrapper<HTMLInputElement> => {
        const wrapper = shallowMount(KeywordsSearchInput, {
            global: { ...getGlobalTestOptions() },
            props: { filters_store },
        });

        return wrapper.find("[data-test=keywords-input]");
    };

    it("Should do nothing when the user did not press the Enter key", async () => {
        const input = getInput();
        await input.setValue("security");
        await input.trigger("keyup", { key: "Shift" });

        expect(filters_store.getFilters().value).toHaveLength(0);
    });

    it("Should not create a filter when the user presses Enter while the trimmed query is an empty string", async () => {
        const input = getInput();
        await input.setValue(" ");
        await input.trigger("keyup", { key: "Enter" });

        expect(filters_store.getFilters().value).toHaveLength(0);
    });

    it("Should create a filter from the query typed in the input each time the user presses Enter", async () => {
        const input = getInput();
        const filter_builder = KeywordFilterBuilder(GettextStub);
        const security_keyword = "security";
        const emergency_keyword = "emergency";

        await input.setValue(security_keyword);
        await input.trigger("keyup", { key: "Enter" });

        expect(filters_store.getFilters().value).toStrictEqual([
            filter_builder.fromKeyword(0, security_keyword),
        ]);
        expect(input.element.value).toBe("");

        await input.setValue(emergency_keyword);
        await input.trigger("keyup", { key: "Enter" });

        expect(filters_store.getFilters().value).toStrictEqual([
            filter_builder.fromKeyword(0, security_keyword),
            filter_builder.fromKeyword(1, emergency_keyword),
        ]);
        expect(input.element.value).toBe("");
    });
});
