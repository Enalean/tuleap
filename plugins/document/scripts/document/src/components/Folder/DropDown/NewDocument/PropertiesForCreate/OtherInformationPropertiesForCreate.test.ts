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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import OtherInformationPropertiesForCreate from "./OtherInformationPropertiesForCreate.vue";
import { TYPE_FILE } from "../../../../../constants";
import type { Property } from "../../../../../type";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import { IS_OBSOLESCENCE_DATE_PROPERTY_USED, PROJECT } from "../../../../../configuration-keys";
import { ProjectBuilder } from "../../../../../../tests/builders/ProjectBuilder";
import { PROJECT_PROPERTIES } from "../../../../../injection-keys";
import { ref } from "vue";
import { okAsync } from "neverthrow";
import { ItemBuilder } from "../../../../../../tests/builders/ItemBuilder";

vi.mock("../../../../../helpers/emitter");

describe("OtherInformationPropertiesForCreate", () => {
    let load_properties: MockInstance;

    beforeEach(() => {
        load_properties = vi.fn();
        load_properties.mockReset();
    });

    function createWrapper(
        value: string,
        is_obsolescence_date_property_used: boolean,
        has_loaded_properties: boolean,
    ): VueWrapper<InstanceType<typeof OtherInformationPropertiesForCreate>> {
        const properties: Array<Property> = [];
        return shallowMount(OtherInformationPropertiesForCreate, {
            props: {
                currentlyUpdatedItem: new ItemBuilder(123)
                    .withProperties(properties)
                    .withType(TYPE_FILE)
                    .withTitle("title")
                    .build(),
                value,
                document_properties: { loadProjectProperties: load_properties },
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                    [IS_OBSOLESCENCE_DATE_PROPERTY_USED.valueOf()]:
                        is_obsolescence_date_property_used,
                    [PROJECT_PROPERTIES.valueOf()]: ref(has_loaded_properties ? [] : null),
                },
            },
        });
    }

    it(`Given obsolescence date is enabled for project
        Then we should display the obsolescence date component`, () => {
        const wrapper = createWrapper("", true, true);

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given obsolescence date is disabled for project
        Then obsolescence date component is not rendered`, () => {
        const wrapper = createWrapper("", false, true);

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given custom component are loading
        Then it displays spinner`, () => {
        load_properties.mockReturnValue(okAsync([]));
        const wrapper = createWrapper("", true, false);

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-other-information-spinner]").exists(),
        ).toBeTruthy();
    });

    it("Load project properties at first load", () => {
        load_properties.mockReturnValue(okAsync([]));
        createWrapper("", true, false);

        expect(load_properties).toHaveBeenCalled();
    });
});
