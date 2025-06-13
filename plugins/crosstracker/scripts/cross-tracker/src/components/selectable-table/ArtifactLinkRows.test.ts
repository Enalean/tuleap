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
import { okAsync } from "neverthrow";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import { ArtifactsTableBuilder as ArtifactsTableBuilderForTests } from "../../../tests/builders/ArtifactsTableBuilder";
import type { ArtifactRow } from "../../domain/ArtifactsTable";
import { NUMERIC_CELL, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { ColumnName } from "../../domain/ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import type { RetrieveArtifactLinks } from "../../domain/RetrieveArtifactLinks";
import { RETRIEVE_ARTIFACT_LINKS } from "../../injection-symbols";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";
import EditCell from "./EditCell.vue";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";
import SelectableCell from "./SelectableCell.vue";

const RetrieveArtifactLinksTableStub = {
    withContent(
        forward_links: ArtifactsTableWithTotal,
        reverse_links: ArtifactsTableWithTotal,
    ): RetrieveArtifactLinks {
        return {
            getForwardLinks: () => okAsync(forward_links),
            getReverseLinks: () => okAsync(reverse_links),
        };
    },

    withDefaultContent(): RetrieveArtifactLinks {
        return {
            getForwardLinks: () => okAsync(new ArtifactsTableBuilderForTests().buildWithTotal(0)),
            getReverseLinks: () => okAsync(new ArtifactsTableBuilderForTests().buildWithTotal(0)),
        };
    },
};

vi.useFakeTimers();

const NUMERIC_COLUMN_NAME = "remaining_effort";

describe("ArtifactLinkRows", () => {
    let number_of_forward_link: number,
        number_of_reverse_link: number,
        artifact_links_table_retriever: RetrieveArtifactLinks,
        artifact_row: ArtifactRow,
        forward_table: ArtifactsTableWithTotal,
        reverse_table: ArtifactsTableWithTotal;

    beforeEach(() => {
        number_of_forward_link = 1;
        number_of_reverse_link = 1;
        artifact_links_table_retriever = RetrieveArtifactLinksTableStub.withDefaultContent();

        artifact_row = new ArtifactRowBuilder()
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
            .buildWithNumberOfLinks(number_of_forward_link, number_of_reverse_link);

        forward_table = new ArtifactsTableBuilderForTests()
            .withColumn(PRETTY_TITLE_COLUMN_NAME)
            .withColumn(NUMERIC_COLUMN_NAME)
            .withArtifactRow(artifact_row)
            .withArtifactRow(artifact_row)
            .buildWithTotal(2);

        reverse_table = new ArtifactsTableBuilderForTests()
            .withColumn(PRETTY_TITLE_COLUMN_NAME)
            .withColumn(NUMERIC_COLUMN_NAME)
            .withArtifactRow(artifact_row)
            .buildWithTotal(2);
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ArtifactLinkRows>> {
        return shallowMount(ArtifactLinkRows, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [RETRIEVE_ARTIFACT_LINKS.valueOf()]: artifact_links_table_retriever,
                },
            },
            props: {
                artifact_id: 477,
                query_id: "0196d46b-aa17-7249-816a-b23604f5721a",
                row: new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .buildWithNumberOfLinks(number_of_forward_link, number_of_reverse_link),
                columns: new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME),
                level: 0,
            },
        });
    }

    it("should display skeletons components for forward and reverse links", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(2);
        expect(skeletons[0].props("link_type")).toBe("forward");
        expect(skeletons[1].props("link_type")).toBe("reverse");
    });

    it("should propagate its own level to skeletons", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(2);
        expect(skeletons[0].props("level")).toBe(wrapper.props("level"));
        expect(skeletons[1].props("level")).toBe(wrapper.props("level"));
    });

    it("should only display forward skeleton component, when there is no reverse links", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 0;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(1);
        expect(skeletons[0].props("link_type")).toBe("forward");
    });

    it("should only display reverse skeleton component, when there is no forward links", () => {
        number_of_forward_link = 0;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(1);
        expect(skeletons[0].props("link_type")).toBe("reverse");
    });

    it("should display 2 forward links and one reverse link after a successful request", async () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;

        artifact_links_table_retriever = RetrieveArtifactLinksTableStub.withContent(
            forward_table,
            reverse_table,
        );

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(0);
        expect(wrapper.findAllComponents(EditCell)).toHaveLength(3);
    });

    it("should propagate its own level to selectable cells", async () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;

        artifact_links_table_retriever = RetrieveArtifactLinksTableStub.withContent(
            forward_table,
            reverse_table,
        );

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        const selectable_cells = wrapper.findAllComponents(SelectableCell);

        selectable_cells.forEach((cell) => {
            expect(cell.props("level")).toBe(wrapper.props("level"));
        });
    });

    it("should propagate one level deeper to further ArtifactLinkRows", async () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;

        artifact_links_table_retriever = RetrieveArtifactLinksTableStub.withContent(
            forward_table,
            reverse_table,
        );

        const wrapper = getWrapper();
        await vi.runOnlyPendingTimersAsync();

        const artifact_link_rows = wrapper.findAllComponents({ name: "ArtifactLinkRows" });

        artifact_link_rows.forEach((artifact_link_row) => {
            expect(artifact_link_row.props("level")).toBe(wrapper.props("level") + 1);
        });
    });
});
