/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReferencesList from "./ReferencesList.vue";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { PROJECT } from "../../configuration-keys";
import * as rest_querier from "../../api/references-rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import ReferencesListError from "./ReferencesListError.vue";
import CrossReference from "./CrossReference.vue";
import { nextTick } from "vue";

describe("ReferencesList", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof ReferencesList>> {
        return shallowMount(ReferencesList, {
            props: { item: new ItemBuilder(123).build() },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: 102,
                },
            },
        });
    }

    it("Should display error message when api fetch failed", async () => {
        const getItemReferences = vi
            .spyOn(rest_querier, "getItemReferences")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));

        const wrapper = getWrapper();

        expect(getItemReferences).toHaveBeenCalled();
        await nextTick();
        expect(wrapper.findComponent(ReferencesListError).exists()).toBe(true);
    });

    it("Should display empty state when no references at all", async () => {
        const getItemReferences = vi.spyOn(rest_querier, "getItemReferences").mockReturnValue(
            okAsync({
                sources_by_nature: [],
                targets_by_nature: [],
                has_source: false,
                has_target: false,
            }),
        );

        const wrapper = getWrapper();

        expect(getItemReferences).toHaveBeenCalled();
        await nextTick();
        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("Should display cross references", async () => {
        const getItemReferences = vi.spyOn(rest_querier, "getItemReferences").mockReturnValue(
            okAsync({
                sources_by_nature: [
                    {
                        label: "Artifacts",
                        icon: "",
                        sections: [],
                    },
                ],
                targets_by_nature: [],
                has_source: true,
                has_target: false,
            }),
        );

        const wrapper = getWrapper();

        expect(getItemReferences).toHaveBeenCalled();
        await nextTick();
        expect(wrapper.findComponent(CrossReference).exists()).toBe(true);
    });
});
