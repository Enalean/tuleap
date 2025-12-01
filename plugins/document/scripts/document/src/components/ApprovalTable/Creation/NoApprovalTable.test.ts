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
import type { Item } from "../../../type";
import NoApprovalTable from "./NoApprovalTable.vue";
import { PROJECT } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import ApprovalTableCreationModal from "./ApprovalTableCreationModal.vue";

describe("NoApprovalTable", () => {
    function getWrapper(item: Item): VueWrapper<InstanceType<typeof NoApprovalTable>> {
        return shallowMount(NoApprovalTable, {
            props: { item },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    it.each([
        ["Should display create button if user can write", true],
        ["Should not display create button if user cannot write", false],
    ])(`%s`, (_: string, can_write: boolean) => {
        const wrapper = getWrapper(new ItemBuilder(123).withUserCanWrite(can_write).build());

        expect(wrapper.findComponent(ApprovalTableCreationModal).exists()).toBe(can_write);
    });
});
