/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { mount, type VueWrapper } from "@vue/test-utils";
import { describe, it, expect } from "vitest";
import { Option } from "@tuleap/option";
import SelectableCell from "./SelectableCell.vue";
import TextCell from "./TextCell.vue";
import UserValue from "./UserValue.vue";
import PrettyTitleCellComponent from "./PrettyTitleCellComponent.vue";
import LinkTypeCellComponent from "./LinkTypeCellComponent.vue";
import {
    DATE_CELL,
    NUMERIC_CELL,
    PROJECT_CELL,
    TEXT_CELL,
    USER_CELL,
    STATIC_LIST_CELL,
    USER_LIST_CELL,
    USER_GROUP_LIST_CELL,
    TRACKER_CELL,
    LINK_TYPE_CELL,
    PRETTY_TITLE_CELL,
    UNKNOWN_CELL,
} from "../../domain/ArtifactsTable";
import type { Cell, ArtifactRow } from "../../domain/ArtifactsTable";
import { DATE_FORMATTER, DATE_TIME_FORMATTER } from "../../injection-symbols";
import { en_US_LOCALE } from "@tuleap/core-constants";
import { IntlFormatter } from "@tuleap/date-helper";
import { v4 as uuidv4 } from "uuid";
import type { TableDataState } from "../TableWrapper.vue";

describe("SelectableCell.vue", () => {
    const row_entry = {
        parent_row_uuid: null,
        row: {
            row_uuid: uuidv4(),
        } as ArtifactRow,
    };

    function createWrapper(cell: Cell): VueWrapper<InstanceType<typeof SelectableCell>> {
        return mount(SelectableCell, {
            props: {
                cell,
                row_entry,
                table_state: {} as TableDataState,
            },
            global: {
                provide: {
                    [DATE_FORMATTER.valueOf()]: IntlFormatter(en_US_LOCALE, "Europe/Paris", "date"),
                    [DATE_TIME_FORMATTER.valueOf()]: IntlFormatter(
                        en_US_LOCALE,
                        "Europe/Paris",
                        "date-with-time",
                    ),
                },
                stubs: {
                    TextCell: true,
                    UserValue: true,
                    PrettyTitleCellComponent: true,
                    LinkTypeCellComponent: true,
                },
            },
        });
    }

    it("renders TextCell for TEXT_CELL type", () => {
        const cell: Cell = {
            type: TEXT_CELL,
            value: "Text content",
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.findComponent(TextCell).exists()).toBe(true);
        expect(wrapper.findComponent(TextCell).props("text")).toBe("Text content");
    });

    it("renders TextCell for UNKNOWN_CELL type", () => {
        const cell: Cell = {
            type: UNKNOWN_CELL,
            value: "Unknown content",
        } as unknown as Cell;

        const wrapper = createWrapper(cell);

        expect(wrapper.findComponent(TextCell).exists()).toBe(true);
        expect(wrapper.findComponent(TextCell).props("text")).toBe("Unknown content");
    });

    it("renders UserValue for USER_CELL type", () => {
        const cell: Cell = {
            type: USER_CELL,
            display_name: "John Doe",
            avatar_uri: "/avatar_uri/",
            user_uri: Option.fromValue("/user_uri/"),
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.findComponent(UserValue).exists()).toBe(true);
        expect(wrapper.findComponent(UserValue).props("user")).toEqual(cell);
    });

    it("renders static list items for STATIC_LIST_CELL type", () => {
        const cell: Cell = {
            type: STATIC_LIST_CELL,
            value: [
                { label: "Item 1", color: Option.fromNullable("army-green") },
                { label: "Item 2", color: Option.nothing() },
            ],
        };

        const wrapper = createWrapper(cell);

        const items = wrapper.findAll(".cell > span");
        expect(items.length).toBe(2);
        expect(items[0].text()).toBe("Item 1");
        expect(items[1].text()).toBe("Item 2");
        expect(items[0].classes()).toContain("tlp-badge-army-green");
        expect(items[1].classes()).toContain("tlp-badge-secondary");
    });

    it("renders UserValue list for USER_LIST_CELL type", () => {
        const cell: Cell = {
            type: USER_LIST_CELL,
            value: [
                {
                    display_name: "User 1",
                    avatar_uri: "/avatar_uri/",
                    user_uri: Option.fromValue("/user_uri/"),
                },
                {
                    display_name: "User 2",
                    avatar_uri: "/avatar_uri2/",
                    user_uri: Option.fromValue("/user_uri2/"),
                },
            ],
        };

        const wrapper = createWrapper(cell);

        const userValues = wrapper.findAllComponents(UserValue);
        expect(userValues.length).toBe(2);
        expect(userValues[0].props("user").display_name).toStrictEqual("User 1");
        expect(userValues[1].props("user").display_name).toStrictEqual("User 2");
    });

    it("renders user group list for USER_GROUP_LIST_CELL type", () => {
        const cell: Cell = {
            type: USER_GROUP_LIST_CELL,
            value: [{ label: "Group 1" }, { label: "Group 2" }],
        };

        const wrapper = createWrapper(cell);

        const groups = wrapper.findAll(".user-group");
        expect(groups.length).toBe(2);
        expect(groups[0].text()).toBe("Group 1");
        expect(groups[1].text()).toBe("Group 2");
    });

    it("renders tracker badge for TRACKER_CELL type", () => {
        const cell: Cell = {
            type: TRACKER_CELL,
            name: "Bug tracker",
            color: "army-green",
        };

        const wrapper = createWrapper(cell);

        const badge = wrapper.find(".cell > span");
        expect(badge.text()).toBe("Bug tracker");
        expect(badge.classes()).toContain("tlp-badge-army-green");
        expect(badge.classes()).toContain("tlp-badge-outline");
    });

    it("renders LinkTypeCellComponent for LINK_TYPE_CELL type", () => {
        const cell: Cell = {
            type: LINK_TYPE_CELL,
            label: "is child of",
            direction: "forward",
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.findComponent(LinkTypeCellComponent).exists()).toBe(true);
        expect(wrapper.findComponent(LinkTypeCellComponent).props("cell")).toEqual(cell);
    });

    it("renders PrettyTitleCellComponent for PRETTY_TITLE_CELL type", () => {
        const cell: Cell = {
            type: PRETTY_TITLE_CELL,
            title: "Pretty title",
            tracker_name: "my_tracker",
            color: "army-green",
            artifact_id: 1,
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.findComponent(PrettyTitleCellComponent).exists()).toBe(true);
        expect(wrapper.findComponent(PrettyTitleCellComponent).props("cell")).toEqual(cell);
        expect(wrapper.findComponent(PrettyTitleCellComponent).props("row_entry")).toStrictEqual(
            row_entry,
        );
    });

    it("formats DATE_CELL without time correctly", () => {
        const cell: Cell = {
            type: DATE_CELL,
            value: Option.fromValue("2023-10-15"),
            with_time: false,
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("2023-10-15");
    });

    it("formats DATE_CELL with time correctly", () => {
        const cell: Cell = {
            type: DATE_CELL,
            value: Option.fromValue("2023-10-15T14:30:00+02:00"),
            with_time: true,
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("2023-10-15 14:30");
    });

    it("formats DATE_CELL with empty value correctly", () => {
        const cell: Cell = {
            type: DATE_CELL,
            value: Option.nothing(),
            with_time: false,
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("");
    });

    it("formats NUMERIC_CELL correctly", () => {
        const cell: Cell = {
            type: NUMERIC_CELL,
            value: Option.fromValue(42),
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("42");
    });

    it("formats NUMERIC_CELL with empty value correctly", () => {
        const cell: Cell = {
            type: NUMERIC_CELL,
            value: Option.nothing(),
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("");
    });

    it("formats PROJECT_CELL with icon correctly", () => {
        const cell: Cell = {
            type: PROJECT_CELL,
            name: "Project X",
            icon: "🚀",
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("🚀 Project X");
    });

    it("formats PROJECT_CELL without icon correctly", () => {
        const cell: Cell = {
            type: PROJECT_CELL,
            name: "Project Y",
            icon: "",
        };

        const wrapper = createWrapper(cell);

        expect(wrapper.find(".cell").text()).toBe("Project Y");
    });
});
