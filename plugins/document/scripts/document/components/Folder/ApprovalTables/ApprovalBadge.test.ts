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

import { shallowMount } from "@vue/test-utils";
import type { Wrapper } from "@vue/test-utils";
import ApprovalBadge from "./ApprovalBadge.vue";
import { TYPE_EMBEDDED } from "../../../constants";
import type { ApprovableDocument, Embedded } from "../../../type";
import localVue from "../../../helpers/local-vue";
import Vue from "vue";

describe("ApprovalBadge", () => {
    function createWrapper(
        item: ApprovableDocument,
        isInFolderContentRow: boolean
    ): Wrapper<ApprovalBadge> {
        return shallowMount(ApprovalBadge, {
            localVue,
            propsData: { item, isInFolderContentRow },
        });
    }

    it(`Given document has no approval status
        When we display approval badge
        Then we should not display anything`, () => {
        const item = {
            id: 42,
            title: "my unlocked document",
            type: TYPE_EMBEDDED,
        } as Embedded;

        const wrapper = createWrapper(item, false);

        expect(wrapper.find(".document-approval-badge").exists()).toBeFalsy();
    });

    it(`Given document has approval status
        When we display approval badge
        Then we should display the corresponding badge`, async () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            approval_table: {
                approval_state: "Approved",
            },
        } as Embedded;

        const wrapper = await createWrapper(item, false);

        expect(wrapper.find(".document-approval-badge").exists()).toBeTruthy();
        expect(wrapper.vm.$data.approval_data.icon_badge).toBe("fa-tlp-gavel-approved");
        expect(wrapper.vm.$data.approval_data.badge_label).toBe("Approved");
        expect(wrapper.vm.$data.approval_data.badge_class).toBe("tlp-badge-success ");
    });

    it(`Given document has approval status
        When we display approval badge
        Then we should display the corresponding badge
        And if the status change
        Then the badge should change as well`, async () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            approval_table: {
                approval_state: "Approved",
            },
        } as Embedded;

        const wrapper = await createWrapper(item, false);

        expect(wrapper.find(".document-approval-badge").exists()).toBeTruthy();
        expect(wrapper.vm.$data.approval_data.icon_badge).toBe("fa-tlp-gavel-approved");
        expect(wrapper.vm.$data.approval_data.badge_label).toBe("Approved");
        expect(wrapper.vm.$data.approval_data.badge_class).toBe("tlp-badge-success ");

        Vue.set(item, "approval_table", { approval_state: "Not yet" });
        await Vue.nextTick();

        expect(wrapper.vm.$data.approval_data.icon_badge).toBe("fa-tlp-gavel-pending");
        expect(wrapper.vm.$data.approval_data.badge_label).toBe("Not yet");
        expect(wrapper.vm.$data.approval_data.badge_class).toBe("tlp-badge-secondary ");
    });

    it(`Given document has approval status and given we are in floder content row
        When we display approval badge
        Then we should display the corresponding badge with custom classes`, async () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            approval_table: {
                approval_state: "Approved",
            },
        } as Embedded;

        const wrapper = await createWrapper(item, true);

        expect(wrapper.find(".document-approval-badge").exists()).toBeTruthy();
        expect(wrapper.vm.$data.approval_data.icon_badge).toBe("fa-tlp-gavel-approved");
        expect(wrapper.vm.$data.approval_data.badge_label).toBe("Approved");
        expect(wrapper.vm.$data.approval_data.badge_class).toBe(
            "tlp-badge-success document-tree-item-toggle-quicklook-approval-badge"
        );
    });
});
