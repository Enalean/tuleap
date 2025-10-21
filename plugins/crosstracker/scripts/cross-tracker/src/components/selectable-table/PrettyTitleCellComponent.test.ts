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
import { nextTick } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ArtifactRow } from "../../domain/ArtifactsTable";
import { NO_DIRECTION, FORWARD_DIRECTION, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import PrettyTitleCellComponent from "./PrettyTitleCellComponent.vue";
import ArtifactLinkArrow from "./ArtifactLinkArrow.vue";
import {
    ARROW_DATA_STORE,
    DASHBOARD_ID,
    DASHBOARD_TYPE,
    TABLE_DATA_STORE,
    TABLE_WRAPPER_OPERATIONS,
} from "../../injection-symbols";
import { PROJECT_DASHBOARD, USER_DASHBOARD } from "../../domain/DashboardType";
import { TableDataStore } from "../../domain/TableDataStore";
import { ArrowDataStore } from "../../domain/ArrowDataStore";
import { v4 as uuidv4 } from "uuid";
import type { TableDataState, TableWrapperOperations } from "../TableWrapper.vue";
import * as has_expandable_links from "../../domain/CheckExpandableLink";
import * as get_number_of_parents from "../../domain/NumberOfParentForRowCalculator";

vi.mock("../../domain/CheckExpandableLink");
vi.mock("../../domain/IsRowALastElementChecker");
vi.mock("../../domain/NumberOfParentForRowCalculator");

describe("PrettyTitleCellComponent", () => {
    let artifact_uri: string;
    let direction = NO_DIRECTION;
    let dashboard_type: string;
    let table_data_store: TableDataStore;
    let arrow_data_store: ArrowDataStore;
    let mock_table_wrapper_operations: TableWrapperOperations;
    let parent_row_uuid: string | null = null;

    const parent_uuid = uuidv4();
    const row_uuid = uuidv4();

    beforeEach(() => {
        artifact_uri = "/plugins/tracker/?aid=286";
        direction = NO_DIRECTION;
        dashboard_type = PROJECT_DASHBOARD;
        table_data_store = TableDataStore();
        table_data_store.addEntry({
            row: { row_uuid: parent_uuid } as ArtifactRow,
            parent_row_uuid: null,
        });
        table_data_store.addEntry({
            row: { row_uuid: row_uuid } as ArtifactRow,
            parent_row_uuid: parent_uuid,
        });
        arrow_data_store = ArrowDataStore();
        arrow_data_store.addEntry(parent_uuid, {} as HTMLElement, {} as HTMLElement);

        mock_table_wrapper_operations = {
            expandRow: vi.fn(),
            collapseRow: vi.fn(),
            loadAllArtifacts: vi.fn(),
        };

        vi.spyOn(has_expandable_links, "hasExpandableLinks").mockReturnValue(true);
        vi.spyOn(get_number_of_parents, "getNumberOfParent").mockReturnValue(0);
    });

    const getWrapper = (): VueWrapper<InstanceType<typeof PrettyTitleCellComponent>> => {
        return shallowMount(PrettyTitleCellComponent, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [DASHBOARD_TYPE.valueOf()]: dashboard_type,
                    [DASHBOARD_ID.valueOf()]: 22,
                    [TABLE_DATA_STORE.valueOf()]: table_data_store,
                    [ARROW_DATA_STORE.valueOf()]: arrow_data_store,
                    [TABLE_WRAPPER_OPERATIONS.valueOf()]: mock_table_wrapper_operations,
                },
            },
            props: {
                row_entry: {
                    row: {
                        row_uuid,
                        artifact_uri,
                        direction,
                    } as ArtifactRow,
                    parent_row_uuid,
                },
                cell: {
                    type: PRETTY_TITLE_CELL,
                    title: "uncensorable litigant",
                    tracker_name: "story",
                    artifact_id: 76,
                    color: "coral-pink",
                },
                table_state: {} as TableDataState,
            },
        });
    };

    it("when the cell is a pretty title, it renders a link to artifact URI and redirect user to project dashboard", () => {
        artifact_uri = "/plugins/tracker/?aid=76";
        const wrapper = getWrapper();

        expect(wrapper.get("a").attributes("href")).toBe(`${artifact_uri}&project-dashboard-id=22`);
    });

    it("when the cell is a pretty title, it renders a link to artifact URI and redirect user to user dashboard", () => {
        dashboard_type = USER_DASHBOARD;
        artifact_uri = "/plugins/tracker/?aid=76";
        const wrapper = getWrapper();

        expect(wrapper.get("a").attributes("href")).toBe(`${artifact_uri}&my-dashboard-id=22`);
    });

    describe("Button", () => {
        it("should display the button when row has expandable links", () => {
            vi.spyOn(has_expandable_links, "hasExpandableLinks").mockReturnValue(true);

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("false");
        });

        it("should NOT display the button when row does NOT have any children", () => {
            vi.spyOn(has_expandable_links, "hasExpandableLinks").mockReturnValue(false);

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("true");
        });
    });

    describe("Caret display", () => {
        const caret_right = "fa-caret-right";
        const caret_down = "fa-caret-down";

        it("should display a caret down, when caret is clicked", async () => {
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_down);
        });

        it("should display a caret right, when caret is clicked again", async () => {
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_down);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
        });
    });

    describe("ArtifactLinkArrow", () => {
        it("should not include an ArtifactLinkArrow if there are no parent elements", () => {
            const wrapper = getWrapper();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(false);
        });

        it("should include an ArtifactLinkArrow if there are parent elements", async () => {
            direction = FORWARD_DIRECTION;
            parent_row_uuid = parent_uuid;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(true);
        });

        it("should include an ArtifactLinkArrow if there are parent elements BUT no reverse links", async () => {
            vi.spyOn(get_number_of_parents, "getNumberOfParent").mockReturnValue(2);
            direction = FORWARD_DIRECTION;
            parent_row_uuid = parent_uuid;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(true);
        });

        it("should NOT include an ArtifactLinkArrow when link has no direction", async () => {
            vi.spyOn(get_number_of_parents, "getNumberOfParent").mockReturnValue(2);
            direction = NO_DIRECTION;
            parent_row_uuid = parent_uuid;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(false);
        });
    });

    describe("Mount and unmount", () => {
        it("should register the element into the ArrowDataStore on Mount", () => {
            expect(arrow_data_store.getByUUID(row_uuid)).toBeUndefined();
            getWrapper();
            expect(arrow_data_store.getByUUID(row_uuid)).not.toBeUndefined();
        });
        it("should remove the element from the ArrowDataStore on unMount", () => {
            const wrapper = getWrapper();
            expect(arrow_data_store.getByUUID(row_uuid)).not.toBeUndefined();

            wrapper.unmount();
            expect(arrow_data_store.getByUUID(row_uuid)).toBeUndefined();
        });
    });

    it("should expand/collapse row", () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=pretty-title-links-button]").trigger("click");
        expect(mock_table_wrapper_operations.expandRow).toHaveBeenCalled();

        wrapper.find("[data-test=pretty-title-links-button]").trigger("click");
        expect(mock_table_wrapper_operations.collapseRow).toHaveBeenCalled();

        wrapper.find("[data-test=pretty-title-links-button]").trigger("click");
        expect(mock_table_wrapper_operations.expandRow).toHaveBeenCalled();
    });
});
