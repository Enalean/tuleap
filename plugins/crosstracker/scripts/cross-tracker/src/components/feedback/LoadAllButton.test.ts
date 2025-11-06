/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import LoadAllConfirmationModal from "./LoadAllConfirmationModal.vue";
import LoadAllButton from "./LoadAllButton.vue";
import type { RowEntry } from "../../domain/TableDataStore";
import type { ArtifactRow } from "../../domain/ArtifactsTable";
import { TABLE_WRAPPER_OPERATIONS } from "../../injection-symbols";
import type { TableWrapperOperations } from "../TableWrapper.vue";

describe("LoadAllButton", () => {
    let mock_table_wrapper_operations: TableWrapperOperations;
    const row_entry = {
        parent_row_uuid: null,
        row: {} as ArtifactRow,
    } as RowEntry;

    beforeEach(() => {
        mock_table_wrapper_operations = {
            expandRow: vi.fn(),
            collapseRow: vi.fn(),
            loadAllArtifacts: vi.fn(),
        };
    });
    function getWrapper(): VueWrapper {
        return shallowMount(LoadAllButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [TABLE_WRAPPER_OPERATIONS.valueOf()]: mock_table_wrapper_operations,
                },
            },
            props: {
                row_entry,
            },
        });
    }

    it("should not display a confirmation modal", () => {
        const wrapper = getWrapper();
        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);

        expect(confirmation_modal.exists()).toBe(false);
    });

    it("should display a confirmation modal, when the 'Load all' button is clicked", async () => {
        const wrapper = getWrapper();

        wrapper.find("[data-test=load-all-button]").trigger("click");
        await wrapper.vm.$nextTick();

        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);
        expect(confirmation_modal.exists()).toBe(true);
    });

    it(`when should-load-all event is triggered with true
        it should not display the confirmation modal
        AND it should emit an load-all event
        AND it should disabled the 'Load all' button
        `, async () => {
        const wrapper = getWrapper();
        const load_all_button = wrapper.find("[data-test=load-all-button]");

        expect(load_all_button.attributes("disabled")).toBeUndefined();

        load_all_button.trigger("click");
        await wrapper.vm.$nextTick();

        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);
        confirmation_modal.vm.$emit("should-load-all", true);
        await wrapper.vm.$nextTick();

        expect(confirmation_modal.exists()).toBe(false);

        expect(mock_table_wrapper_operations.loadAllArtifacts).toHaveBeenCalledWith(row_entry);
        expect(load_all_button.attributes("disabled")).toBeDefined();
    });

    it("should not display the confirmation modal, and it should not emit an load-all event, when should-load-all event is triggered with false", async () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=load-all-button]").trigger("click");
        await wrapper.vm.$nextTick();

        const confirmation_modal = wrapper.findComponent(LoadAllConfirmationModal);
        confirmation_modal.vm.$emit("should-load-all", false);
        await wrapper.vm.$nextTick();

        expect(confirmation_modal.exists()).toBe(false);

        expect(mock_table_wrapper_operations.loadAllArtifacts).not.toHaveBeenCalledWith(row_entry);
    });
});
