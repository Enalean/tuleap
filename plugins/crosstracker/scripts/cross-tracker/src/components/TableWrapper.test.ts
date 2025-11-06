/*
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import {
    ARROW_REDRAW_TRIGGERER,
    EMITTER,
    TABLE_DATA_ORCHESTRATOR,
    TABLE_DATA_STORE,
} from "../injection-symbols";
import type { TableWrapperOperations } from "./TableWrapper.vue";
import TableWrapper from "./TableWrapper.vue";
import type { TableDataOrchestrator } from "../domain/TableDataOrchestrator";
import type { ArtifactRow } from "../domain/ArtifactsTable";
import { v4 as uuidv4 } from "uuid";
import SelectableTable from "./selectable-table/SelectableTable.vue";
import { TableDataStore } from "../domain/TableDataStore";
import type { ArrowRedrawTriggerer } from "../ArrowRedrawTriggerer";
import { okAsync } from "neverthrow";
import { NOTIFY_FAULT_EVENT, SEARCH_ARTIFACTS_SUCCESS_EVENT } from "../helpers/widget-events";

vi.useFakeTimers();

describe("TableWrapper", () => {
    let mock_table_data_orchestrator: TableDataOrchestrator;
    let table_data_store: TableDataStore;
    let stub_arrow_redrawer_triggerer: ArrowRedrawTriggerer;
    const columns = new Set(["col1", "col2"]);
    const first_row = { row_uuid: uuidv4() } as ArtifactRow;
    const second_row = { row_uuid: uuidv4() } as ArtifactRow;
    const orchestrator_result_with_two_rows_and_two_columns = {
        result: {
            row_collection: [first_row, second_row],
            columns: new Set(["col1", "col2"]),
        },
        total: 2,
    };

    beforeEach(() => {
        mock_table_data_orchestrator = {
            loadTopLevelArtifacts: vi.fn(),
            loadForwardArtifactLinks: vi.fn(),
            loadReverseArtifactLinks: vi.fn(),
            closeArtifactRow: vi.fn(),
        } as unknown as TableDataOrchestrator;
        table_data_store = TableDataStore();
        stub_arrow_redrawer_triggerer = {
            listenToSelectableTableResize: vi.fn(),
            removeListener: vi.fn(),
        };
    });
    function getWrapper(tql_query: string): VueWrapper<InstanceType<typeof TableWrapper>> {
        return shallowMount(TableWrapper, {
            global: {
                provide: {
                    [TABLE_DATA_ORCHESTRATOR.valueOf()]: mock_table_data_orchestrator,
                    [TABLE_DATA_STORE.valueOf()]: table_data_store,
                    [ARROW_REDRAW_TRIGGERER.valueOf()]: stub_arrow_redrawer_triggerer,
                    [SEARCH_ARTIFACTS_SUCCESS_EVENT.valueOf()]: vi.fn(),
                    [NOTIFY_FAULT_EVENT.valueOf()]: vi.fn(),
                    [EMITTER.valueOf()]: vi.fn(),
                },
            },
            props: {
                tql_query,
            },
        });
    }

    it("should render empty state when artifacts are loading", () => {
        mock_table_data_orchestrator.loadTopLevelArtifacts = vi
            .fn()
            .mockResolvedValue(orchestrator_result_with_two_rows_and_two_columns);

        const tql_query = "SELECT @pretty_title FROM @project = 'self'";
        const wrapper = getWrapper(tql_query);

        expect(wrapper.find("[data-test=loading]").exists()).toBe(true);
        expect(wrapper.find("[data-test=selectable-table]").exists()).toBe(false);
    });

    it("should not call API when no query TQL are defined", async () => {
        const tql_query = "";
        const wrapper = getWrapper(tql_query);
        await vi.runOnlyPendingTimersAsync();

        expect(mock_table_data_orchestrator.loadTopLevelArtifacts).not.toHaveBeenCalled();

        expect(wrapper.find("[data-test=loading]").exists()).toBe(false);
        expect(wrapper.find("[data-test=selectable-table]").exists()).toBe(true);
    });
    it("should load top level artifacts", async () => {
        mock_table_data_orchestrator.loadTopLevelArtifacts = vi
            .fn()
            .mockResolvedValue(orchestrator_result_with_two_rows_and_two_columns);

        const tql_query = "SELECT @pretty_title FROM @project = 'self'";
        const wrapper = getWrapper(tql_query);

        expect(mock_table_data_orchestrator.loadTopLevelArtifacts).toHaveBeenCalled();
        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=loading]").exists()).toBe(false);
        expect(wrapper.find("[data-test=selectable-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pagination]").exists()).toBe(true);
    });

    it("should NOT display pagination pane when there when there are no results ", async () => {
        const orchestrator_result_with_total = {
            result: {
                row_collection: [],
                columns,
            },
            total: 0,
        };

        mock_table_data_orchestrator.loadTopLevelArtifacts = vi
            .fn()
            .mockResolvedValue(orchestrator_result_with_total);

        const tql_query = "SELECT @pretty_title FROM @project = 'self'";
        const wrapper = getWrapper(tql_query);
        await vi.runOnlyPendingTimersAsync();

        expect(mock_table_data_orchestrator.loadTopLevelArtifacts).toHaveBeenCalled();

        expect(wrapper.find("[data-test=loading]").exists()).toBe(false);
        expect(wrapper.find("[data-test=selectable-table]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pagination]").exists()).toBe(false);
    });

    it("should load forward and reverse artifact links on expandRow and close them on collapseRow", async () => {
        const already_loaded_row = first_row;
        mock_table_data_orchestrator.loadTopLevelArtifacts = vi
            .fn()
            .mockResolvedValue(orchestrator_result_with_two_rows_and_two_columns);

        mock_table_data_orchestrator.loadForwardArtifactLinks = vi.fn().mockResolvedValue({});

        const reverse_links_results = {
            row_collection: [already_loaded_row, second_row],
            columns: columns,
        };
        mock_table_data_orchestrator.loadReverseArtifactLinks = vi
            .fn()
            .mockResolvedValue(reverse_links_results);

        const tql_query = "SELECT @pretty_title FROM @project = 'self'";
        const wrapper = getWrapper(tql_query);
        const injection = wrapper.vm as unknown as TableWrapperOperations;

        injection.expandRow(first_row);

        await vi.runOnlyPendingTimersAsync();

        expect(
            wrapper.findComponent(SelectableTable).props("table_state").row_collection,
        ).toStrictEqual(reverse_links_results.row_collection);
        expect(wrapper.findComponent(SelectableTable).props("table_state").columns).toStrictEqual(
            reverse_links_results.columns,
        );

        mock_table_data_orchestrator.closeArtifactRow = vi.fn().mockReturnValue({
            row_collection: [],
            columns: new Set(),
        });
        injection.collapseRow(first_row);

        await vi.runOnlyPendingTimersAsync();
        expect(
            wrapper.findComponent(SelectableTable).props("table_state").row_collection,
        ).toStrictEqual([]);
    });

    it("should load all forward and reverse link on loadAll", async () => {
        const already_loaded_row = first_row;
        table_data_store.addEntry({ row: second_row, parent_row_uuid: null });
        table_data_store.addEntry({
            row: already_loaded_row,
            parent_row_uuid: second_row.row_uuid,
        });
        mock_table_data_orchestrator.loadTopLevelArtifacts = vi
            .fn()
            .mockResolvedValue(orchestrator_result_with_two_rows_and_two_columns);

        mock_table_data_orchestrator.loadAllForwardArtifactLinks = vi
            .fn()
            .mockReturnValue(okAsync({}));

        const reverse_links_results = {
            row_collection: [already_loaded_row, second_row],
            columns: columns,
        };
        mock_table_data_orchestrator.loadAllReverseArtifactLinks = vi
            .fn()
            .mockReturnValue(okAsync(reverse_links_results));

        const tql_query = "SELECT @pretty_title FROM @project = 'self'";
        const wrapper = getWrapper(tql_query);
        const injection = wrapper.vm as unknown as TableWrapperOperations;

        injection.loadAllArtifacts({ row: first_row, parent_row_uuid: second_row.row_uuid });

        await vi.runOnlyPendingTimersAsync();

        expect(
            wrapper.findComponent(SelectableTable).props("table_state").row_collection,
        ).toStrictEqual(reverse_links_results.row_collection);
        expect(wrapper.findComponent(SelectableTable).props("table_state").columns).toStrictEqual(
            reverse_links_results.columns,
        );
    });
});
