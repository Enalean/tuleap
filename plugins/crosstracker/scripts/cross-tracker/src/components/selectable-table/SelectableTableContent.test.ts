/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { expect, describe, it, vi } from "vitest";
import type { ArtifactLinkLoadError } from "../../domain/TableDataOrchestrator";
import { v4 as uuidv4 } from "uuid";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SelectableTableContent from "./SelectableTableContent.vue";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/ArtifactsTable";
import type { RowEntry } from "../../domain/TableDataStore";
import SelectableTableContentRow from "./SelectableTableContentRow.vue";
import RowErrorMessage from "../feedback/RowErrorMessage.vue";
import * as check_row_have_displayed_links from "../../domain/CheckRowHaveDisplayedLinks";
import LoadAllButton from "../feedback/LoadAllButton.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";

vi.mock("../../domain/CheckRowHaveDisplayedLinks");

describe(`SelectableTableContent`, () => {
    const forward_row_uuid = uuidv4();
    const reverse_row_uuid = uuidv4();
    const forward_row: RowEntry = {
        parent_row_uuid: null,
        row: {
            row_uuid: forward_row_uuid,
            artifact_id: 123,
            artifact_uri: "/plugins/tracker/?aid=123",
            cells: new Map(),
            expected_number_of_forward_links: 2,
            expected_number_of_reverse_links: 1,
            direction: FORWARD_DIRECTION,
        },
    };
    const reverse_row: RowEntry = {
        parent_row_uuid: null,
        row: {
            row_uuid: reverse_row_uuid,
            artifact_id: 456,
            artifact_uri: "/plugins/tracker/?aid=456",
            cells: new Map(),
            expected_number_of_forward_links: 2,
            expected_number_of_reverse_links: 1,
            direction: REVERSE_DIRECTION,
        },
    };

    const getWrapper = (
        row_collection: RowEntry[],
        uuids_of_error_rows: ArtifactLinkLoadError[],
    ): VueWrapper<InstanceType<typeof SelectableTableContent>> => {
        return shallowMount(SelectableTableContent, {
            props: {
                table_state: {
                    row_collection,
                    columns: new Set(["column1", "column2"]),
                    uuids_of_loading_rows: [],
                    uuids_of_error_rows,
                },
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    };

    it(`will display selectable content row`, () => {
        const wrapper = getWrapper([forward_row, reverse_row], []);

        const content_rows = wrapper.findAllComponents(SelectableTableContentRow);
        expect(content_rows.length).toBe(2);
        expect(wrapper.findComponent(RowErrorMessage).exists()).toBe(false);
        expect(wrapper.findComponent(LoadAllButton).exists()).toBe(false);
    });

    it(`will display error linked to row if link load have failed`, () => {
        const errors = [
            {
                row_uuid: forward_row_uuid,
                error: "Oops, something happen",
                direction: FORWARD_DIRECTION,
            } as ArtifactLinkLoadError,
        ];

        const wrapper = getWrapper([forward_row], errors);
        const content_rows = wrapper.findAllComponents(SelectableTableContentRow);
        expect(content_rows.length).toBe(1);

        const error_message = wrapper.findAllComponents(RowErrorMessage);
        expect(error_message.length).toBe(1);
    });

    it(`will display a load all button if needed`, () => {
        vi.spyOn(
            check_row_have_displayed_links,
            "isLastVisibleChildWithMoreUnloadedSiblings",
        ).mockReturnValue(true);

        const wrapper = getWrapper([forward_row, reverse_row], []);
        expect(wrapper.findComponent(LoadAllButton).exists()).toBe(true);
    });
});
