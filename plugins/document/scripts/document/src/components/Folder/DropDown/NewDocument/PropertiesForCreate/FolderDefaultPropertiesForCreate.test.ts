/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FolderDefaultPropertiesForCreate from "./FolderDefaultPropertiesForCreate.vue";
import type { ListValue, Property } from "../../../../../type";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import { IS_STATUS_PROPERTY_USED, PROJECT } from "../../../../../configuration-keys";
import { ProjectBuilder } from "../../../../../../tests/builders/ProjectBuilder";
import { getDocumentProperties } from "../../../../../helpers/properties/document-properties";
import { PROJECT_PROPERTIES } from "../../../../../injection-keys";
import { ref } from "vue";

describe("FolderDefaultPropertiesForCreate", () => {
    function createWrapper(
        status_value: string,
        properties: Array<Property>,
        is_status_property_used: boolean,
    ): VueWrapper<InstanceType<typeof FolderDefaultPropertiesForCreate>> {
        return shallowMount(FolderDefaultPropertiesForCreate, {
            props: { status_value, properties, document_properties: getDocumentProperties() },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                    [IS_STATUS_PROPERTY_USED.valueOf()]: is_status_property_used,
                    [PROJECT_PROPERTIES.valueOf()]: ref([]),
                },
            },
        });
    }

    describe("Component display -", () => {
        it(`Given project uses status, default properties are rendered`, () => {
            const list_values = [
                {
                    id: 103,
                } as ListValue,
            ];
            const properties = [
                {
                    short_name: "status",
                    list_value: list_values,
                } as Property,
            ];
            const wrapper = createWrapper("rejected", properties, true);

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists(),
            ).toBeTruthy();
        });
        it(`Given item has custom property, default properties are rendered`, () => {
            const list_values = [
                {
                    id: 103,
                } as ListValue,
            ];
            const properties = [
                {
                    short_name: "field_",
                    list_value: list_values,
                } as Property,
            ];
            const wrapper = createWrapper("rejected", properties, true);

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists(),
            ).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-folder-default-properties]").exists(),
            ).toBeTruthy();
        });
        it(`Given item has no custom property and status is not available, default properties are not rendered`, () => {
            const properties: Array<Property> = [];

            const wrapper = createWrapper("rejected", properties, false);

            expect(
                wrapper.find("[data-test=document-folder-default-properties-container]").exists(),
            ).toBeFalsy();
        });
    });
});
