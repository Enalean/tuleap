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
import { TYPE_EMPTY } from "../../constants";
import NoApprovalTable from "./NoApprovalTable.vue";
import { ApprovalTableBuilder } from "../../../tests/builders/ApprovalTableBuilder";
import CurrentApprovalTable from "./Display/CurrentApprovalTable.vue";

vi.useFakeTimers();

describe("DisplayApprovalTable", () => {
    const load_document = vi.fn();

    function getWrapper(): VueWrapper<InstanceType<typeof DisplayApprovalTable>> {
        return shallowMount(DisplayApprovalTable, {
            props: {
                item_id: 123,
            },
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocumentWithAscendentHierarchy: load_document,
                    },
                }),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(102).build(),
                },
            },
        });
    }

    it("Should display error when item is not approvable", async () => {
        load_document.mockResolvedValue(new ItemBuilder(123).withType(TYPE_EMPTY).build());
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=error-not-approvable]").exists()).toBe(true);
    });

    it("Should display NoApprovalTable when... no approval table", async () => {
        load_document.mockResolvedValue(new ItemBuilder(123).buildApprovableDocument());
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(NoApprovalTable).exists()).toBe(true);
    });

    it("Should display button with link to old ui", async () => {
        load_document.mockResolvedValue(
            new ItemBuilder(123)
                .withApprovalTable(new ApprovalTableBuilder(35).build())
                .buildApprovableDocument(),
        );
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(CurrentApprovalTable).exists()).toBe(true);
    });
});
