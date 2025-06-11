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

import { describe, it, expect } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { ArtifactRowBuilder } from "../../../../tests/builders/ArtifactRowBuilder";
import type { ColumnName } from "../../../domain/ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "../../../domain/ColumnName";
import { DATE_CELL, PRETTY_TITLE_CELL } from "../../../domain/ArtifactsTable";
import ArtifactLinkRowSkeleton from "./ArtifactLinkRowSkeleton.vue";
import EmptyEditCell from "./EmptyEditCell.vue";
import EmptySelectableCell from "./EmptySelectableCell.vue";

const DATE_COLUMN_NAME = "start_date";
const columns = new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME).add(DATE_COLUMN_NAME);

describe("ArtifactLinkRowSkeleton", () => {
    let number_of_forward_link = 0,
        number_of_reverse_link = 0,
        link_type: "forward" | "reverse" = "forward";

    function getWrapper(): VueWrapper {
        return shallowMount(ArtifactLinkRowSkeleton, {
            global: { ...getGlobalTestOptions() },
            props: {
                row: new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(DATE_COLUMN_NAME, {
                        type: DATE_CELL,
                        value: Option.fromValue("2021-09-26T07:40:03+09:00"),
                        with_time: true,
                    })
                    .buildWithNumberOfLinks(number_of_forward_link, number_of_reverse_link),
                columns,
                link_type,
                level: 0,
            },
        });
    }

    describe("Forward links", () => {
        it("should display the correct number of row and the correct number of cell", () => {
            number_of_forward_link = 4;
            link_type = "forward";
            const wrapper = getWrapper();

            expect(wrapper.findAllComponents(EmptyEditCell)).toHaveLength(number_of_forward_link);
            expect(wrapper.findAllComponents(EmptySelectableCell)).toHaveLength(
                number_of_forward_link * columns.size,
            );
        });
    });

    describe("Reverse links", () => {
        it("should display the correct number of row and the correct number of cell", () => {
            number_of_reverse_link = 2;
            link_type = "reverse";
            const wrapper = getWrapper();

            expect(wrapper.findAllComponents(EmptyEditCell)).toHaveLength(number_of_reverse_link);
            expect(wrapper.findAllComponents(EmptySelectableCell)).toHaveLength(
                number_of_reverse_link * columns.size,
            );
        });
    });
});
