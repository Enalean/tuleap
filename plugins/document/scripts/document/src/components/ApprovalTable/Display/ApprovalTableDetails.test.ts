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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ApprovalTableDetails from "./ApprovalTableDetails.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { ApprovalTable } from "../../../type";
import { ApprovalTableBuilder } from "../../../../tests/builders/ApprovalTableBuilder";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";

describe("ApprovalTableDetails", () => {
    function getWrapper(
        table: ApprovalTable,
    ): VueWrapper<InstanceType<typeof ApprovalTableDetails>> {
        return shallowMount(ApprovalTableDetails, {
            props: {
                table,
                item: new ItemBuilder(123).build(),
                is_readonly: false,
            },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it("Should display table details", () => {
        const wrapper = getWrapper(
            new ApprovalTableBuilder(35)
                .withVersionNumber(3)
                .withNotificationType("disabled")
                .withDescription("Lorem ipsum...")
                .build(),
        );

        expect(wrapper.find("[data-test=table-version-number]").text()).toBe("3");
        expect(wrapper.find("[data-test=table-notification]").text()).toBe("Disabled");
        expect(wrapper.find("[data-test=table-closed]").exists()).toBe(false);
        expect(wrapper.find("[data-test=table-description]").text()).toBe("Lorem ipsum...");
    });
});
