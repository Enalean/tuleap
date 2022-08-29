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

import { shallowMount } from "@vue/test-utils";
import SearchResults from "./SearchResults.vue";
import { createSwitchToLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Project, UserHistoryEntry } from "../../../type";
import type { RootGetters } from "../../../store/getters";

describe("SearchResults", () => {
    it(`should display No results
        when the list of filtered projects and the list of filtered recent items are empty`, async () => {
        const wrapper = shallowMount(SearchResults, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    getters: {
                        filtered_projects: [] as Project[],
                        filtered_history: { entries: [] as UserHistoryEntry[] },
                    },
                }),
            },
        });

        expect(wrapper.text()).toContain("No results");
    });

    it.each([
        [[] as Project[], [{}] as UserHistoryEntry[]],
        [[{}] as Project[], [] as UserHistoryEntry[]],
        [[{}] as Project[], [{}] as UserHistoryEntry[]],
    ])(
        `should not display anything
        when there is at least one matching project %s or recent item %s
        because FTS is not implemented yet and we don't want to display a "No results" which may confuse people.`,
        async (filtered_projects, filtered_history_entries) => {
            const wrapper = shallowMount(SearchResults, {
                localVue: await createSwitchToLocalVue(),
                mocks: {
                    $store: createStoreMock({
                        getters: {
                            filtered_projects,
                            filtered_history: { entries: filtered_history_entries },
                        } as RootGetters,
                    }),
                },
            });

            expect(wrapper.element).toMatchInlineSnapshot(`<!---->`);
        }
    );
});
