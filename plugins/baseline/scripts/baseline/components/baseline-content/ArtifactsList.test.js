/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import Vuex from "vuex";
import { createList } from "../../support/factories";
import ContentArtifact from "./ContentArtifact.vue";
import ArtifactsList from "./ArtifactsList.vue";

describe("ArtifactsList", () => {
    let wrapper;

    beforeEach(() => {
        const store = new Vuex.Store({
            modules: {
                current_baseline: {
                    namespaced: true,
                    getters: {
                        isLimitReachedOnArtifact: () => () => false,
                        filterArtifacts: () => (artifacts) => artifacts,
                        findArtifactsByIds: () => () => [],
                    },
                },
            },
        });
        wrapper = shallowMount(ArtifactsList, {
            propsData: {
                artifacts: createList("baseline_artifact", 3),
            },
            localVue,
            store,
        });
    });

    it("shows as many artifacts as given", () => {
        expect(wrapper.findAllComponents(ContentArtifact)).toHaveLength(3);
    });
});
