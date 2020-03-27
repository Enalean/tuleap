/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import Vuex from "vuex";
import { shallowMount } from "@vue/test-utils";
import ApprovalTableBadge from "./ApprovalTableBadge.vue";
import localVue from "../../../helpers/local-vue.js";
import { TYPE_EMBEDDED } from "../../../constants.js";

describe("ApprovalTableBadge", () => {
    let approval_badge_factory, store;

    beforeEach(() => {
        store = new Vuex.Store();

        approval_badge_factory = (props = {}) => {
            return shallowMount(ApprovalTableBadge, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given document has no approval status
        When we display approval badge
        Then we should not display anything`, () => {
        const item = {
            id: 42,
            title: "my unlocked document",
            type: TYPE_EMBEDDED,
        };

        const wrapper = approval_badge_factory({
            item,
        });

        expect(wrapper.contains(".document-approval-badge")).toBeFalsy();
    });

    it(`Given document has no approval status
        When we display approval badge
        Then we should not display the corresponding badge`, () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            approval_table: {
                approval_state: "Approved",
            },
        };

        const wrapper = approval_badge_factory({
            item,
        });

        expect(wrapper.contains(".document-approval-badge")).toBeTruthy();
        expect(wrapper.vm.approval_data.icon_badge).toBe("fa-tlp-gavel-approved");
        expect(wrapper.vm.approval_data.badge_label).toBe("Approved");
        expect(wrapper.vm.approval_data.badge_class).toBe("tlp-badge-success ");
    });
});
