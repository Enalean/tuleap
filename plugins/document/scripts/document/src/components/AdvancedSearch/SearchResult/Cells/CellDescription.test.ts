/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import type { ItemSearchResult } from "../../../../type";
import { shallowMount } from "@vue/test-utils";
import CellDescription from "./CellDescription.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("CellDescription", () => {
    it("should display the preprocessed description", () => {
        const wrapper = shallowMount(CellDescription, {
            props: {
                item: {
                    post_processed_description: "ipsum doloret",
                } as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({}),
                directives: {
                    "dompurify-html": vi.fn(),
                },
            },
        });

        expect(wrapper.vm.get_post_processed_description).toBe("ipsum doloret");
    });
});
