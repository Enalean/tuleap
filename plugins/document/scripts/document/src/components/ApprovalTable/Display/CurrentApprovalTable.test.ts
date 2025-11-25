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
import CurrentApprovalTable from "./CurrentApprovalTable.vue";
import type { ApprovableDocument, Item } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { PROJECT } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import { ApprovalTableBuilder } from "../../../../tests/builders/ApprovalTableBuilder";
import ApprovalTableDetails from "./ApprovalTableDetails.vue";

describe("CurrentApprovalTable", () => {
    function getWrapper(
        item: Item & ApprovableDocument,
    ): VueWrapper<InstanceType<typeof CurrentApprovalTable>> {
        return shallowMount(CurrentApprovalTable, {
            props: { item },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(102).build(),
                },
            },
        });
    }

    it.each([
        ["It should display admin button if user can write", true],
        ["It should not display admin button if user cannot write", false],
    ])(`%s`, (_: string, can_write: boolean) => {
        const wrapper = getWrapper(
            new ItemBuilder(123).withUserCanWrite(can_write).buildApprovableDocument(),
        );

        expect(wrapper.find("[data-test=table-admin-button]").exists()).toBe(can_write);
    });

    it("Should tell table is not yet available", () => {
        const wrapper = getWrapper(
            new ItemBuilder(123).withApprovalTableEnabled(false).buildApprovableDocument(),
        );

        expect(wrapper.find("[data-test=table-not-available]").exists()).toBe(true);
    });

    it("Should display approval table details", () => {
        const wrapper = getWrapper(
            new ItemBuilder(123)
                .withApprovalTableEnabled(true)
                .withApprovalTable(new ApprovalTableBuilder(35).build())
                .buildApprovableDocument(),
        );

        expect(wrapper.findComponent(ApprovalTableDetails).exists()).toBe(true);
    });
});
