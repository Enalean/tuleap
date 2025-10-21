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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { GET_COLUMN_NAME } from "../../injection-symbols";
import { ColumnNameGetter } from "../../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../../helpers/vue-gettext-provider-for-test";
import type { ColumnName } from "../../domain/ColumnName";
import {
    PROJECT_COLUMN_NAME,
    DESCRIPTION_COLUMN_NAME,
    PRETTY_TITLE_COLUMN_NAME,
} from "../../domain/ColumnName";
import SelectableTableHeader from "./SelectableTableHeader.vue";
import type { ArtifactsTable } from "../../domain/ArtifactsTable";

describe("SelectableTableHeader", () => {
    function getWrapper(
        columns: ArtifactsTable["columns"],
    ): VueWrapper<InstanceType<typeof SelectableTableHeader>> {
        return shallowMount(SelectableTableHeader, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [GET_COLUMN_NAME.valueOf()]: ColumnNameGetter(
                        createVueGettextProviderPassThrough(),
                    ),
                },
            },
            props: {
                columns,
            },
        });
    }

    it("should render column headers based on the table data store", () => {
        const columns = new Set<ColumnName>()
            .add(PRETTY_TITLE_COLUMN_NAME)
            .add(DESCRIPTION_COLUMN_NAME);

        const wrapper = getWrapper(columns);
        const header_cells = wrapper.findAll('[data-test="column-header"]');
        expect(header_cells).toHaveLength(2);
        expect(header_cells[0].text()).toBe("Artifact");
        expect(header_cells[1].text()).toBe("Description");
    });

    it.each([
        ["is-last-cell-of-row", "last cell of row"],
        ["is-pretty-title-column", "pretty title column"],
    ])(`It should apply %s to %s`, (expected_class) => {
        const columns = new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME);

        const wrapper = getWrapper(columns);
        const header_cells = wrapper.findAll('[data-test="column-header"]');
        expect(header_cells).toHaveLength(1);
        expect(header_cells[0].classes()).toContain(expected_class);
    });

    it("should determine the last cell of row", () => {
        const columns = new Set<ColumnName>()
            .add(PRETTY_TITLE_COLUMN_NAME)
            .add(PROJECT_COLUMN_NAME)
            .add(DESCRIPTION_COLUMN_NAME);

        const wrapper = getWrapper(columns);
        const header_cells = wrapper.findAll('[data-test="column-header"]');
        expect(header_cells).toHaveLength(3);
        expect(header_cells[0].classes()).not.toContain("is-last-cell-of-row");
        expect(header_cells[1].classes()).not.toContain("is-last-cell-of-row");
        expect(header_cells[2].classes()).toContain("is-last-cell-of-row");
    });
});
