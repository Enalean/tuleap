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

import { describe, it, beforeEach, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { ArtifactsTableBuilder } from "../../../tests/builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import type { ArtifactRow as ArtifactRowType, ArtifactsTable } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, NUMERIC_CELL, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { type ColumnName, PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";
import ArtifactRows from "./ArtifactRows.vue";

const NUMERIC_COLUMN_NAME = "remaining_effort";

describe("ArtifactLinkRows", () => {
    let number_of_forward_link: number,
        artifact_row: ArtifactRowType,
        artifact_table: ArtifactsTable;

    beforeEach(() => {
        number_of_forward_link = 2;
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
            .build();

        artifact_table = new ArtifactsTableBuilder()
            .withColumn(PRETTY_TITLE_COLUMN_NAME)
            .withColumn(NUMERIC_COLUMN_NAME)
            .withArtifactRow(artifact_row)
            .withArtifactRow(artifact_row)
            .build();
    });

    function getWrapper(is_loading: boolean): VueWrapper<InstanceType<typeof ArtifactLinkRows>> {
        return shallowMount(ArtifactLinkRows, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                is_loading,
                tql_query: "SELECT @id FROM @project='self' WHERE @id>1",
                row: new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .buildWithExpectedNumberOfLinks(number_of_forward_link, 0),
                columns: new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME),
                level: 0,
                artifact_links_rows: artifact_table.rows,
                expected_number_of_links: number_of_forward_link,
                parent_element: {} as HTMLElement,
                parent_caret: {} as HTMLElement,
                direction: FORWARD_DIRECTION,
                reverse_links_count: 2,
                ancestors: [123, 234],
            },
        });
    }

    it("should display skeleton component when links are loading and should propagate its own level", () => {
        const wrapper = getWrapper(true);

        const skeleton = wrapper.findComponent(ArtifactLinkRowSkeleton);
        const artifact_row = wrapper.findComponent(ArtifactRows);

        expect(artifact_row.exists()).toBe(false);
        expect(skeleton.exists()).toBe(true);
        expect(skeleton.props("expected_number_of_links")).toBe(number_of_forward_link);
        expect(skeleton.props("level")).toBe(wrapper.props("level"));
    });

    it("should display artifact rows after a successful request", () => {
        const wrapper = getWrapper(false);

        const skeleton = wrapper.findComponent(ArtifactLinkRowSkeleton);
        const artifact_row = wrapper.findComponent(ArtifactRows);

        expect(artifact_row.exists()).toBe(true);
        expect(skeleton.exists()).toBe(false);
        expect(artifact_row.props("rows")).toStrictEqual(artifact_table.rows);
        expect(artifact_row.props("level")).toBe(wrapper.props("level"));
    });
});
