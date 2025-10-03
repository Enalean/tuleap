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

import { afterEach, beforeEach, describe, expect, it } from "vitest";
import { nextTick } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ArtifactLinkDirection, ArtifactRow } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import PrettyTitleCellComponent from "./PrettyTitleCellComponent.vue";
import CaretIndentation from "./CaretIndentation.vue";
import ArtifactLinkArrow from "./ArtifactLinkArrow.vue";
import {
    ARROW_DATA_STORE,
    DASHBOARD_ID,
    DASHBOARD_TYPE,
    TABLE_DATA_STORE,
} from "../../injection-symbols";
import { PROJECT_DASHBOARD, USER_DASHBOARD } from "../../domain/DashboardType";
import { TableDataStore } from "../../domain/TableDataStore";
import { ArrowDataStore } from "../../domain/ArrowDataStore";
import type { Emitter } from "mitt";
import mitt from "mitt";
import type { Events } from "../../helpers/widget-events";
import { INSERTED_ROW_EVENT } from "../../helpers/widget-events";
import { v4 as uuidv4 } from "uuid";

describe("PrettyTitleCellComponent", () => {
    let artifact_uri: string;
    let expected_number_of_forward_link: number;
    let expected_number_of_reverse_link: number;
    let direction: ArtifactLinkDirection | undefined;
    let reverse_links_count: number | undefined;
    let level: number;
    let dashboard_type: string;
    let emitter: Emitter<Events>;
    let table_data_store: TableDataStore;
    let arrow_data_store: ArrowDataStore;

    const parent_uuid = uuidv4();
    const row_uuid = uuidv4();

    beforeEach(() => {
        artifact_uri = "/plugins/tracker/?aid=286";
        expected_number_of_forward_link = 0;
        expected_number_of_reverse_link = 0;
        level = 0;
        direction = undefined;
        reverse_links_count = undefined;
        dashboard_type = PROJECT_DASHBOARD;
        emitter = mitt<Events>();
        table_data_store = TableDataStore(emitter);
        table_data_store.listen();
        emitter.emit(INSERTED_ROW_EVENT, {
            row: { row_uuid: row_uuid } as ArtifactRow,
            parent_row: { row_uuid: parent_uuid } as ArtifactRow,
        });
        emitter.emit(INSERTED_ROW_EVENT, {
            row: { row_uuid: parent_uuid } as ArtifactRow,
            parent_row: null,
        });
        arrow_data_store = ArrowDataStore();
        arrow_data_store.addEntry(parent_uuid, {} as HTMLElement, {} as HTMLElement);
    });

    afterEach(() => {
        table_data_store.removeListeners();
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
                },
            },
            props: {
                uuid: row_uuid,
                cell: {
                    type: PRETTY_TITLE_CELL,
                    title: "uncensorable litigant",
                    tracker_name: "story",
                    artifact_id: 76,
                    color: "coral-pink",
                },
                artifact_uri,
                expected_number_of_forward_link,
                expected_number_of_reverse_link,
                level,
                is_last: false,
                reverse_links_count,
                direction,
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
        it("should hide the button, when artifact has no links ", () => {
            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("true");
        });

        it("should not hide the button, when artifact has links", () => {
            expected_number_of_forward_link = 2;
            expected_number_of_reverse_link = 1;
            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("false");
        });

        it("should not display the button if level > 0 and there is only one reverse link and no forward links", () => {
            level = 1;
            expected_number_of_forward_link = 0;
            expected_number_of_reverse_link = 1;

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("true");
        });

        it("should display the button if level = 0 and there is only one reverse link and no forward links", () => {
            level = 0;
            expected_number_of_forward_link = 0;
            expected_number_of_reverse_link = 1;

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("false");
        });

        it("should not display the button if level > 0 and there is only one forward link and no reverse links", () => {
            level = 1;
            expected_number_of_forward_link = 1;
            expected_number_of_reverse_link = 0;

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("true");
        });

        it("should not display the button if level = 0 and there is only one forward link and no reverse links", () => {
            level = 0;
            expected_number_of_forward_link = 1;
            expected_number_of_reverse_link = 0;

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("false");
        });

        it("should display the button if level > 0 and there is one reverse link and one forward link", () => {
            level = 1;
            expected_number_of_forward_link = 1;
            expected_number_of_reverse_link = 1;

            const wrapper = getWrapper();

            expect(
                wrapper.find("[data-test=pretty-title-links-button]").attributes("aria-hidden"),
            ).toBe("false");
        });
    });

    describe("Caret display", () => {
        const caret_right = "fa-caret-right";
        const caret_down = "fa-caret-down";

        it("should display a caret down, when caret is clicked", async () => {
            expected_number_of_forward_link = 2;
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_down);
        });

        it("should display a caret right, when caret is clicked again", async () => {
            expected_number_of_reverse_link = 1;
            const wrapper = getWrapper();

            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_down);
            await wrapper.find("[data-test=pretty-title-caret]").trigger("click");
            expect(wrapper.find("[data-test=pretty-title-caret]").classes()).toContain(caret_right);
        });
    });

    describe("Caret indentation", () => {
        it("should display a Caret Indentation component with the same level", () => {
            expected_number_of_forward_link = 2;
            const wrapper = getWrapper();

            expect(wrapper.findComponent(CaretIndentation).props("level")).toBe(
                wrapper.props("level"),
            );
        });
    });

    describe("ArtifactLinkArrow", () => {
        it("should not include an ArtifactLinkArrow if there are no parent elements", () => {
            const wrapper = getWrapper();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(false);
        });

        it("should include an ArtifactLinkArrow if there are parent elements", async () => {
            direction = FORWARD_DIRECTION;
            reverse_links_count = 3;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(true);
        });

        it("should include an ArtifactLinkArrow if there are parent elements BUT no reverse links", async () => {
            direction = FORWARD_DIRECTION;
            reverse_links_count = 0;

            const wrapper = getWrapper();

            await nextTick();

            expect(wrapper.findComponent(ArtifactLinkArrow).exists()).toBe(true);
        });
    });
});
