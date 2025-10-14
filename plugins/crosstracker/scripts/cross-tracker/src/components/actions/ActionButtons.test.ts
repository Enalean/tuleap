/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import ActionButtons from "./ActionButtons.vue";
import { IS_EXPORT_ALLOWED } from "../../injection-symbols";
import ExportXLSXButtonModal from "./xlsx/ExportXLSXButtonModal.vue";
import type { Query } from "../../type";

vi.useFakeTimers();

describe("ActionButtons", () => {
    let is_xlsx_export_allowed: boolean;

    beforeEach(() => {
        is_xlsx_export_allowed = true;
    });

    afterEach(() => {});

    function getWrapper(): VueWrapper<InstanceType<typeof ActionButtons>> {
        const backend_query: Query = {
            id: "",
            tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
            title: "The title of my query",
            description: "",
            is_default: false,
        };
        return shallowMount(ActionButtons, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [IS_EXPORT_ALLOWED.valueOf()]: is_xlsx_export_allowed,
                },
            },
            props: {
                backend_query,
                queries: [backend_query],
                are_query_details_toggled: true,
            },
        });
    }

    describe("XLSX stuff display", () => {
        it("does not display any button if the export is not allowed", () => {
            is_xlsx_export_allowed = false;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ExportXLSXButtonModal).exists()).toBe(false);
        });
        it("displays the export button if the export is  allowed", () => {
            is_xlsx_export_allowed = true;
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ExportXLSXButtonModal).exists()).toBe(true);
        });
    });
});
