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
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { RowEntry } from "../../domain/TableDataStore";
import { v4 as uuidv4 } from "uuid";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SelectableTableContentRow from "./SelectableTableContentRow.vue";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";
import type { TableDataState } from "../TableWrapper.vue";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/ArtifactsTable";
import * as get_number_of_parents from "../../domain/NumberOfParentForRowCalculator";

vi.mock("../../domain/NumberOfParentForRowCalculator");

describe(`SelectableTableContentRow`, () => {
    let row_entry: RowEntry;
    let child_entry: RowEntry;
    let table_state: TableDataState;
    const row_uuid = uuidv4();

    beforeEach(() => {
        row_entry = {
            parent_row_uuid: null,
            row: {
                row_uuid,
                artifact_id: 123,
                artifact_uri: "/plugins/tracker/?aid=123",
                cells: new Map(),
                expected_number_of_forward_links: 5,
                expected_number_of_reverse_links: 2,
                direction: FORWARD_DIRECTION,
            },
        };

        child_entry = {
            parent_row_uuid: row_uuid,
            row: {
                row_uuid: uuidv4(),
                artifact_id: 456,
                artifact_uri: "/plugins/tracker/?aid=456",
                cells: new Map(),
                expected_number_of_forward_links: 0,
                expected_number_of_reverse_links: 0,
                direction: FORWARD_DIRECTION,
            },
        };

        table_state = {
            row_collection: [row_entry, child_entry],
            columns: new Set(["column1", "column2"]),
            uuids_of_loading_rows: [],
            uuids_of_error_rows: [],
        };

        vi.useFakeTimers();
    });

    const getWrapper = (): VueWrapper<InstanceType<typeof SelectableTableContentRow>> => {
        return shallowMount(SelectableTableContentRow, {
            props: {
                row_entry,
                table_state,
            },
        });
    };

    it(`will display content row without skeleton`, () => {
        vi.spyOn(get_number_of_parents, "getNumberOfParent").mockReturnValue(0);
        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=artifact-row]").exists()).toBe(true);
        expect(wrapper.findComponent(ArtifactLinkRowSkeleton).exists()).toBe(false);
    });

    it(`will display content row with skeleton`, () => {
        table_state.uuids_of_loading_rows.push(
            {
                row_uuid,
                direction: FORWARD_DIRECTION,
            },
            {
                row_uuid,
                direction: REVERSE_DIRECTION,
            },
        );
        vi.spyOn(get_number_of_parents, "getNumberOfParent").mockReturnValue(2);
        const wrapper = getWrapper();
        expect(wrapper.find("[data-test=artifact-row]").exists()).toBe(true);
        expect(wrapper.findComponent(ArtifactLinkRowSkeleton).exists()).toBe(true);

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);
        expect(skeletons).toHaveLength(2);
        expect(skeletons[0].props().expected_number_of_links).toBe(5);
        expect(skeletons[1].props().expected_number_of_links).toBe(1);
    });
});
