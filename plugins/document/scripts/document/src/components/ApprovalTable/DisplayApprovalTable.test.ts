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
import DisplayApprovalTable from "./DisplayApprovalTable.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";
import { ProjectBuilder } from "../../../tests/builders/ProjectBuilder";
import { PROJECT } from "../../configuration-keys";

vi.useFakeTimers();

describe("DisplayApprovalTable", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof DisplayApprovalTable>> {
        return shallowMount(DisplayApprovalTable, {
            props: {
                item_id: 123,
            },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocumentWithAscendentHierarchy: vi
                            .fn()
                            .mockResolvedValue(new ItemBuilder(123).build()),
                    },
                }),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(102).build(),
                },
            },
        });
    }

    it("Should display button with link to old ui", async () => {
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.get("[data-test=old-ui-link]").text()).toBe("Switch to old ui");
    });
});
