/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { DEFAULT_LOCALE } from "@tuleap/locale";
import CriterionDate from "./CriterionDate.vue";
import type { SearchDate } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import emitter from "../../../helpers/emitter";
import { USER_LOCALE } from "../../../configuration-keys";
import DateFlatPicker from "../../Folder/DropDown/PropertiesForCreateOrUpdate/DateFlatPicker.vue";

describe("CriterionDate", () => {
    let emitMock: MockInstance;

    beforeEach(() => {
        emitMock = vi.spyOn(emitter, "emit");
    });

    function getWrapper(value: SearchDate | null): VueWrapper {
        return shallowMount(CriterionDate, {
            props: {
                criterion: {
                    name: "create_date",
                    label: "Creation date",
                },
                value,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [USER_LOCALE.valueOf()]: DEFAULT_LOCALE,
                },
            },
        });
    }

    it("should render the component when no date set", () => {
        expect(getWrapper(null).element).toMatchSnapshot();
    });

    it("should render the component when date is set", () => {
        const value: SearchDate = { date: "2022-01-01", operator: "=" };
        expect(getWrapper(value).element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing date", () => {
        const wrapper = getWrapper(null);

        wrapper.findComponent(DateFlatPicker).vm.$emit("input", "2022-01-01");

        const expected: SearchDate = { date: "2022-01-01", operator: ">" };
        expect(emitMock).toHaveBeenCalledWith("update-criteria-date", {
            criteria: "create_date",
            value: expected,
        });
    });

    it("should warn parent component when user is changing operator", () => {
        const wrapper = getWrapper(null);

        wrapper.find("[data-test=equal]").setSelected();

        const expected: SearchDate = { date: "", operator: "=" };
        expect(emitMock).toHaveBeenCalledWith("update-criteria-date", {
            criteria: "create_date",
            value: expected,
        });
    });
});
