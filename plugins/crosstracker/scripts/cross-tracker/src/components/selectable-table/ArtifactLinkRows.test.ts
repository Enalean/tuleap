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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import { PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { ColumnName } from "../../domain/ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";

describe("ArtifactLinkRows", () => {
    let number_of_forward_link = 0,
        number_of_reverse_link = 0;

    function getWrapper(): VueWrapper {
        return shallowMount(ArtifactLinkRows, {
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
                    .buildWithNumberOfLinks(number_of_forward_link, number_of_reverse_link),
                columns: new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME),
            },
        });
    }

    it("should display skeletons components for forward and reverse links", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=forward-link-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=reverse-link-skeleton]").exists()).toBe(true);
    });

    it("should only display forward skeleton component, when there is no reverse links", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 0;
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=forward-link-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=reverse-link-skeleton]").exists()).toBe(false);
    });

    it("should only display reverse skeleton component, when there is no forward links", () => {
        number_of_forward_link = 0;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=forward-link-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=reverse-link-skeleton]").exists()).toBe(true);
    });
});
