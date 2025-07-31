/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { ReadonlyFieldDate } from "@/sections/readonly-fields/ReadonlyFields";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldDate from "@/components/section/readonly-fields/FieldDate.vue";
import { createGettext } from "vue3-gettext";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import { DISPLAY_TYPE_BLOCK } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { USER_PREFERENCES } from "@/user-preferences-injection-key";

describe("FieldDate", () => {
    const getWrapper = (field: ReadonlyFieldDate): VueWrapper => {
        return shallowMount(FieldDate, {
            props: {
                field,
            },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [USER_PREFERENCES.valueOf()]: {
                        locale: "fr_FR",
                        timezone: "Europe/Paris",
                        relative_date_display: "absolute_first-relative_tooltip",
                    },
                },
            },
        });
    };

    it("When the field has no values, then it should display empty state", () => {
        const wrapper = getWrapper(ReadonlyFieldStub.dateField(null, false, DISPLAY_TYPE_BLOCK));

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("Should display the date field value", () => {
        const wrapper = getWrapper(
            ReadonlyFieldStub.dateField("2025-07-28T09:07:52+02:00", true, DISPLAY_TYPE_BLOCK),
        );

        const date_element = wrapper.find("tlp-relative-date");
        expect(date_element.exists()).toBe(true);
        expect(date_element.text()).toStrictEqual("28/07/2025 09:07");
    });
});
