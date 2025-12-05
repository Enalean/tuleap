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
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import NoApprovalTable from "./Creation/NoApprovalTable.vue";
import { ApprovalTableBuilder } from "../../../tests/builders/ApprovalTableBuilder";
import CurrentApprovalTable from "./Display/CurrentApprovalTable.vue";
import ApprovalTableHistory from "./History/ApprovalTableHistory.vue";
import ApprovalTableAdministration from "./Administration/ApprovalTableAdministration.vue";

vi.useFakeTimers();

describe("DisplayApprovalTable", () => {
    const load_document = vi.fn();

    function getWrapper(): VueWrapper<InstanceType<typeof DisplayApprovalTable>> {
        return shallowMount(DisplayApprovalTable, {
            props: {
                item_id: 123,
                version: null,
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

    it("Should display current table", async () => {
        load_document.mockResolvedValue(
            new ItemBuilder(123)
                .withApprovalTable(new ApprovalTableBuilder(35).build())
                .buildApprovableDocument(),
        );
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(CurrentApprovalTable).exists()).toBe(true);
    });

    it.each([
        ["Should display history for file", TYPE_FILE, true],
        ["Should display history for link", TYPE_LINK, true],
        ["Should display history for embedded file", TYPE_EMBEDDED, true],
        ["Should display history for wiki", TYPE_WIKI, true],
        ["Should not display history for folder", TYPE_FOLDER, false],
    ])(`%s`, async (_: string, type: string, should_display: boolean) => {
        load_document.mockResolvedValue(
            new ItemBuilder(123)
                .withType(type)
                .withApprovalTable(new ApprovalTableBuilder(35).build())
                .buildApprovableDocument(),
        );
        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();
        expect(wrapper.findComponent(ApprovalTableHistory).exists()).toBe(should_display);
    });

    it.each([
        ["It should display admin button if user can write", true],
        ["It should not display admin button if user cannot write", false],
    ])(`%s`, async (_: string, can_write: boolean) => {
        const item = new ItemBuilder(123)
            .withUserCanWrite(can_write)
            .withApprovalTable(new ApprovalTableBuilder(35).build())
            .buildApprovableDocument();
        load_document.mockResolvedValue(item);
        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();
        expect(wrapper.findComponent(ApprovalTableAdministration).exists()).toBe(can_write);
    });
});
