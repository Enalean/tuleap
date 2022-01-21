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
import SearchCriteriaPanel from "./SearchCriteriaPanel.vue";
import localVue from "../../helpers/local-vue";

describe("SearchCriteriaPanel", () => {
    it("should allow user to search for new terms", () => {
        // Need to attach the wrapper to a parent node so that Vue Test Utils can
        // submit the form when clicking on the submit button.
        // See https://github.com/vuejs/vue-test-utils/issues/1030#issuecomment-441166455
        const parent_node = document.createElement("div");
        if (document.body) {
            document.body.appendChild(parent_node);
        }

        const wrapper = shallowMount(SearchCriteriaPanel, {
            localVue,
            attachTo: parent_node,
            propsData: {
                query: "Lorem",
            },
        });

        wrapper.find("[data-test=global-search").setValue("Lorem ipsum");
        wrapper.find("[data-test=submit]").trigger("click");

        expect(wrapper.emitted()["advanced-search"]).toEqual([[{ query: "Lorem ipsum" }]]);

        // Avoid memory leaks when attaching to a parent node.
        // See https://vue-test-utils.vuejs.org/api/options.html#attachto
        wrapper.destroy();
    });
});
