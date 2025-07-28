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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { errAsync, okAsync } from "neverthrow";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { Fault } from "@tuleap/fault";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import { ArtifactsTableBuilder } from "../../../tests/builders/ArtifactsTableBuilder";
import { RetrieveArtifactLinksStub } from "../../../tests/stubs/RetrieveArtifactLinksStub";
import { MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED } from "../../api/ArtifactLinksRetriever";
import { NUMERIC_CELL, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { ColumnName } from "../../domain/ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import type { RetrieveArtifactLinks } from "../../domain/RetrieveArtifactLinks";
import { RETRIEVE_ARTIFACT_LINKS, WIDGET_ID } from "../../injection-symbols";
import RowLoadAllButton from "../feedback/RowLoadAllButton.vue";
import RowErrorMessage from "../feedback/RowErrorMessage.vue";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";
import SelectableCell from "./SelectableCell.vue";
import ArtifactRow from "./ArtifactRow.vue";

vi.useFakeTimers();

const NUMERIC_COLUMN_NAME = "remaining_effort";
const error_message = "Ooops";
const fault = Fault.fromMessage(error_message);
const html_element = {} as HTMLElement;

const artifact_row = new ArtifactRowBuilder()
    .addCell(PRETTY_TITLE_COLUMN_NAME, {
        type: PRETTY_TITLE_CELL,
        title: "earthmaking",
        tracker_name: "lifesome",
        artifact_id: 512,
        color: "inca-silver",
    })
    .addCell(NUMERIC_COLUMN_NAME, {
        type: NUMERIC_CELL,
        value: Option.fromValue(74),
    })
    .buildWithExpectedNumberOfLinks(1, 1);

const forward_table = new ArtifactsTableBuilder()
    .withColumn(PRETTY_TITLE_COLUMN_NAME)
    .withColumn(NUMERIC_COLUMN_NAME)
    .withArtifactRow(artifact_row)
    .withArtifactRow(artifact_row)
    .buildWithTotal(2);

const reverse_table = new ArtifactsTableBuilder()
    .withColumn(PRETTY_TITLE_COLUMN_NAME)
    .withColumn(NUMERIC_COLUMN_NAME)
    .withArtifactRow(artifact_row)
    .buildWithTotal(2);

describe("ArtifactRow", () => {
    let artifact_links_table_retriever: RetrieveArtifactLinks,
        ancestors: number[],
        artifact_id: number,
        level: number;

    beforeEach(() => {
        artifact_id = 512;
        ancestors = [123, 234];
        artifact_links_table_retriever = RetrieveArtifactLinksStub.withDefaultContent();
        level = 0;
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ArtifactRow>> {
        return shallowMount(ArtifactRow, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [RETRIEVE_ARTIFACT_LINKS.valueOf()]: artifact_links_table_retriever,
                    [WIDGET_ID.valueOf()]: 101,
                },
            },
            props: {
                tql_query: 'SELECT @pretty_title FROM @project="self"',
                row: new ArtifactRowBuilder()
                    .withRowId(artifact_id)
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id,
                        color: "inca-silver",
                    })
                    .buildWithExpectedNumberOfLinks(1, 1),
                columns: new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME),
                level,
                is_last: false,
                parent_element: undefined,
                parent_caret: undefined,
                direction: undefined,
                reverse_links_count: undefined,
                ancestors,
            },
        });
    }

    it("should display forward and reverse links when caret is clicked with one level deeper", async () => {
        const getForwardLinks = vi.spyOn(artifact_links_table_retriever, "getForwardLinks");
        const getReverseLinks = vi.spyOn(artifact_links_table_retriever, "getReverseLinks");
        const wrapper = getWrapper();

        wrapper.findComponent(SelectableCell).vm.$emit("toggle-links", html_element, html_element);
        await vi.runOnlyPendingTimersAsync();
        const row_error_message = wrapper.findComponent(RowErrorMessage);
        const artifact_link_rows = wrapper.findAllComponents(ArtifactLinkRows);

        expect(getForwardLinks).toHaveBeenCalledOnce();
        expect(getReverseLinks).toHaveBeenCalledOnce();
        expect(artifact_link_rows).toHaveLength(2);
        expect(row_error_message.exists()).toBe(false);
        expect(artifact_link_rows[0].props("level")).toBe(wrapper.props("level") + 1);
        expect(artifact_link_rows[1].props("level")).toBe(wrapper.props("level") + 1);
    });

    it("should propagate its own level to selectable cells", async () => {
        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        const selectable_cells = wrapper.findAllComponents(SelectableCell);

        selectable_cells.forEach((cell) => {
            expect(cell.props("level")).toBe(wrapper.props("level"));
        });
    });

    it("should not fetch forward or reverse links if they already have been called", async () => {
        const getForwardLinks = vi.spyOn(artifact_links_table_retriever, "getForwardLinks");
        const getReverseLinks = vi.spyOn(artifact_links_table_retriever, "getReverseLinks");
        const wrapper = getWrapper();

        // Expand artifact links for the first time
        await wrapper.findComponent(SelectableCell).trigger("toggle-links");
        await vi.runOnlyPendingTimersAsync();

        expect(getForwardLinks).toHaveBeenCalledTimes(1);
        expect(getReverseLinks).toHaveBeenCalledTimes(1);

        // Hide artifact links
        await wrapper.findComponent(SelectableCell).trigger("toggle-links");
        await vi.runOnlyPendingTimersAsync();

        expect(getForwardLinks).toHaveBeenCalledTimes(1);
        expect(getReverseLinks).toHaveBeenCalledTimes(1);

        // Expand artifact links for the second time
        await wrapper.findComponent(SelectableCell).trigger("toggle-links");
        await vi.runOnlyPendingTimersAsync();

        expect(getForwardLinks).toHaveBeenCalledTimes(1);
        expect(getReverseLinks).toHaveBeenCalledTimes(1);
    });

    it.each([
        ["forward links", errAsync(fault), okAsync(forward_table)],
        ["reverse links", okAsync(reverse_table), errAsync(fault)],
        ["forward and reverse links", errAsync(fault), errAsync(fault)],
    ])(
        "should display an error message if an error occurred when retrieving %s",
        async (name, forward, reverse) => {
            artifact_links_table_retriever = RetrieveArtifactLinksStub.withForwardAndReverseContent(
                forward,
                reverse,
            );
            const wrapper = getWrapper();

            wrapper
                .findComponent(SelectableCell)
                .vm.$emit("toggle-links", html_element, html_element);
            await vi.runOnlyPendingTimersAsync();
            const row_error_message = wrapper.findComponent(RowErrorMessage);

            expect(row_error_message.exists()).toBe(true);
            expect(row_error_message.props("error_message")).toStrictEqual(error_message);
        },
    );

    describe("Load all button", () => {
        it("should not display a load all button if there is less than MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED forward or reverse links", async () => {
            artifact_links_table_retriever = RetrieveArtifactLinksStub.withTotalNumberOfLinks(
                MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED - 5,
                MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED - 1,
            );
            const wrapper = getWrapper();
            wrapper
                .findComponent(SelectableCell)
                .vm.$emit("toggle-links", html_element, html_element);
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(RowLoadAllButton).exists()).toBe(false);
        });

        it.each([
            ["forward", MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED + 9, 3],
            ["reverse", 3, MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED + 16],
            [
                "forward or reverse",
                MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED + 6,
                MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED + 22,
            ],
        ])(
            "should display a load all button if there is more than MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED %s links",
            async (name, number_of_forward_links, number_of_reverse_links) => {
                artifact_links_table_retriever = RetrieveArtifactLinksStub.withTotalNumberOfLinks(
                    number_of_forward_links,
                    number_of_reverse_links,
                );
                const wrapper = getWrapper();
                wrapper
                    .findComponent(SelectableCell)
                    .vm.$emit("toggle-links", html_element, html_element);
                await vi.runOnlyPendingTimersAsync();

                expect(wrapper.findComponent(RowLoadAllButton).exists()).toBe(true);
            },
        );

        it.each([
            ["forward", 59, 3],
            ["reverse", 3, 62],
            ["forward or reverse", 56, 72],
        ])(
            "should fetch all forward and reverse links if load all button is clicked and the button should be hidden",
            async (name, number_of_forward_links, number_of_reverse_links) => {
                ancestors = [456, artifact_id];
                level = 1;
                artifact_links_table_retriever = RetrieveArtifactLinksStub.withTotalNumberOfLinks(
                    number_of_forward_links,
                    number_of_reverse_links,
                );
                const getAllForwardLinks = vi.spyOn(
                    artifact_links_table_retriever,
                    "getAllForwardLinks",
                );
                const getAllReverseLinks = vi.spyOn(
                    artifact_links_table_retriever,
                    "getAllReverseLinks",
                );
                const wrapper = getWrapper();

                wrapper
                    .findComponent(SelectableCell)
                    .vm.$emit("toggle-links", html_element, html_element);
                await vi.runOnlyPendingTimersAsync();

                expect(wrapper.findComponent(RowLoadAllButton).exists()).toBe(true);
                expect(getAllForwardLinks).toHaveBeenCalledTimes(0);
                expect(getAllReverseLinks).toHaveBeenCalledTimes(0);

                wrapper.findComponent(RowLoadAllButton).vm.$emit("click");
                await vi.runOnlyPendingTimersAsync();

                expect(wrapper.findComponent(RowLoadAllButton).exists()).toBe(false);
                expect(getAllForwardLinks).toHaveBeenCalledTimes(1);
                expect(getAllReverseLinks).toHaveBeenCalledTimes(1);
            },
        );
    });

    describe("Ancestors propagation", () => {
        it("Should include its own row into the ancestors collection passed to ArtifactLinks", async () => {
            ancestors = [472];

            const wrapper = getWrapper();

            wrapper
                .findComponent(SelectableCell)
                .vm.$emit("toggle-links", html_element, html_element);

            await vi.runOnlyPendingTimersAsync();

            const artifact_link_rows = wrapper.findAllComponents(ArtifactLinkRows);

            expect(artifact_link_rows).not.toHaveLength(0);
            artifact_link_rows.forEach((artifact_link_row) => {
                expect(artifact_link_row.props("ancestors")).toStrictEqual([472, artifact_id]);
            });
        });
    });

    describe("Parents filtering", () => {
        it.each([
            ["forward", 0],
            ["reverse", 1],
        ])(
            "should not filter anything if my parent is not in the list of %s links",
            async (name, component_index) => {
                const row_1 = new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(NUMERIC_COLUMN_NAME, {
                        type: NUMERIC_CELL,
                        value: Option.fromValue(74),
                    })
                    .withRowId(675)
                    .buildWithExpectedNumberOfLinks(1, 1);

                const row_2 = new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(NUMERIC_COLUMN_NAME, {
                        type: NUMERIC_CELL,
                        value: Option.fromValue(74),
                    })
                    .withRowId(988)
                    .buildWithExpectedNumberOfLinks(1, 1);

                const links_table = new ArtifactsTableBuilder()
                    .withColumn(PRETTY_TITLE_COLUMN_NAME)
                    .withColumn(NUMERIC_COLUMN_NAME)
                    .withArtifactRow(row_1)
                    .withArtifactRow(row_2)
                    .buildWithTotal(2);

                artifact_links_table_retriever =
                    RetrieveArtifactLinksStub.withForwardAndReverseContent(
                        okAsync(links_table),
                        okAsync(links_table),
                    );

                const wrapper = getWrapper();

                wrapper
                    .findComponent(SelectableCell)
                    .vm.$emit("toggle-links", html_element, html_element);
                await vi.runOnlyPendingTimersAsync();

                const artifact_link_rows_component =
                    wrapper.findAllComponents(ArtifactLinkRows)[component_index];
                const artifact_links_rows =
                    artifact_link_rows_component.props("artifact_links_rows");

                expect(artifact_links_rows).toHaveLength(links_table.total);
            },
        );

        it.each([
            ["forward", 0],
            ["reverse", 1],
        ])(
            "should filter my parent out if it is in the list of %s links",
            async (name, index_component) => {
                const row_1 = new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(NUMERIC_COLUMN_NAME, {
                        type: NUMERIC_CELL,
                        value: Option.fromValue(74),
                    })
                    .withRowId(675)
                    .buildWithExpectedNumberOfLinks(1, 1);

                const row_2 = new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(NUMERIC_COLUMN_NAME, {
                        type: NUMERIC_CELL,
                        value: Option.fromValue(74),
                    })
                    .withRowId(artifact_id)
                    .buildWithExpectedNumberOfLinks(1, 1);

                const links_table = new ArtifactsTableBuilder()
                    .withColumn(PRETTY_TITLE_COLUMN_NAME)
                    .withColumn(NUMERIC_COLUMN_NAME)
                    .withArtifactRow(row_1)
                    .withArtifactRow(row_2)
                    .buildWithTotal(2);

                artifact_links_table_retriever =
                    RetrieveArtifactLinksStub.withForwardAndReverseContent(
                        okAsync(links_table),
                        okAsync(links_table),
                    );

                ancestors = [345, 5498, artifact_id];

                const wrapper = getWrapper();

                wrapper
                    .findComponent(SelectableCell)
                    .vm.$emit("toggle-links", html_element, html_element);
                await vi.runOnlyPendingTimersAsync();

                const artifact_link_rows_component =
                    wrapper.findAllComponents(ArtifactLinkRows)[index_component];
                const artifact_links_rows =
                    artifact_link_rows_component.props("artifact_links_rows");

                expect(artifact_links_rows.filter((row) => row.id === artifact_id)).toHaveLength(0);
            },
        );

        it("should work even if I have no ancestors", async () => {
            const reverse_row_1 = new ArtifactRowBuilder()
                .addCell(PRETTY_TITLE_COLUMN_NAME, {
                    type: PRETTY_TITLE_CELL,
                    title: "earthmaking",
                    tracker_name: "lifesome",
                    artifact_id: 512,
                    color: "inca-silver",
                })
                .addCell(NUMERIC_COLUMN_NAME, {
                    type: NUMERIC_CELL,
                    value: Option.fromValue(74),
                })
                .withRowId(675)
                .buildWithExpectedNumberOfLinks(1, 1);

            const reverse_row_2 = new ArtifactRowBuilder()
                .addCell(PRETTY_TITLE_COLUMN_NAME, {
                    type: PRETTY_TITLE_CELL,
                    title: "earthmaking",
                    tracker_name: "lifesome",
                    artifact_id: 512,
                    color: "inca-silver",
                })
                .addCell(NUMERIC_COLUMN_NAME, {
                    type: NUMERIC_CELL,
                    value: Option.fromValue(74),
                })
                .withRowId(artifact_id)
                .buildWithExpectedNumberOfLinks(1, 1);

            const reverse_links_table = new ArtifactsTableBuilder()
                .withColumn(PRETTY_TITLE_COLUMN_NAME)
                .withColumn(NUMERIC_COLUMN_NAME)
                .withArtifactRow(reverse_row_1)
                .withArtifactRow(reverse_row_2)
                .buildWithTotal(2);

            artifact_links_table_retriever = RetrieveArtifactLinksStub.withForwardAndReverseContent(
                okAsync(forward_table),
                okAsync(reverse_links_table),
            );

            ancestors = [];

            const wrapper = getWrapper();

            wrapper
                .findComponent(SelectableCell)
                .vm.$emit("toggle-links", html_element, html_element);
            await vi.runOnlyPendingTimersAsync();

            const reverse_artifact_link_rows = wrapper.findAllComponents(ArtifactLinkRows)[1];
            const reverse_rows = reverse_artifact_link_rows.props("artifact_links_rows");

            expect(reverse_rows).toHaveLength(reverse_links_table.total);
        });
    });
});
