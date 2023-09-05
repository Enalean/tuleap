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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DropDownButton from "./DropDownButton.vue";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import emitter from "../../../helpers/emitter";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

jest.mock("../../../helpers/emitter");

describe("DropDownButton", () => {
    let fake_dropdown_object: Dropdown;
    beforeEach(() => {
        fake_dropdown_object = {
            addEventListener: jest.fn(),
            removeEventListener: jest.fn(),
        } as unknown as Dropdown;

        jest.spyOn(document, "addEventListener");
        jest.spyOn(document, "removeEventListener");
        jest.spyOn(tlp_dropdown, "createDropdown").mockReturnValue(fake_dropdown_object);
    });

    function createWrapper(
        isAppended: boolean,
        isInQuickLookMode: boolean,
        isInLargeMode: boolean,
        isInFolderEmptyState: boolean,
    ): VueWrapper<InstanceType<typeof DropDownButton>> {
        return shallowMount(DropDownButton, {
            props: { isAppended, isInQuickLookMode, isInLargeMode, isInFolderEmptyState },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`Given drop down button is appended (aka user has write permissions)
        When we display the button
        Then it should display the button action and the dropdown option ( | update | v |)`, () => {
        const wrapper = createWrapper(true, false, false, false);

        expect(fake_dropdown_object.addEventListener).toHaveBeenCalledTimes(2);
        expect(document.addEventListener).toHaveBeenCalledTimes(1);

        expect(wrapper.find(".tlp-append").exists()).toBeTruthy();
        expect(wrapper.find(".tlp-button-icon-right").exists()).toBeFalsy();
        expect(wrapper.find(".fa-ellipsis").exists()).toBeFalsy();
    });

    it(`Given drop down button is not appended (aka user has read permissions)
        When we display the button
        Then it should display an ellipsis and the dropdown option (|... v|)`, () => {
        const wrapper = createWrapper(false, false, false, false);

        expect(fake_dropdown_object.addEventListener).toHaveBeenCalledTimes(2);
        expect(document.addEventListener).toHaveBeenCalledTimes(1);

        expect(wrapper.find(".tlp-append").exists()).toBeFalsy();
        expect(wrapper.find(".fa-ellipsis").exists()).toBeTruthy();
    });

    it(`Given drop down button is in quick look mode
        When we display the button
        Then it should be displayed outlined`, () => {
        const wrapper = createWrapper(true, true, false, false);

        expect(fake_dropdown_object.addEventListener).toHaveBeenCalledTimes(2);
        expect(document.addEventListener).toHaveBeenCalledTimes(1);

        expect(wrapper.find(".tlp-button-outline").exists()).toBeTruthy();
    });

    it(`Given drop down button is in large mode
        When we display the button
        Then it should be displayed large`, () => {
        const wrapper = createWrapper(true, true, true, false);

        expect(fake_dropdown_object.addEventListener).toHaveBeenCalledTimes(2);
        expect(document.addEventListener).toHaveBeenCalledTimes(1);

        expect(wrapper.find(".tlp-button-large").exists()).toBeTruthy();
    });

    it(`Hide the dropdown
        When component is destroyed`, () => {
        const wrapper = createWrapper(true, true, true, false);
        wrapper.unmount();

        expect(fake_dropdown_object.addEventListener).toHaveBeenCalledTimes(2);
        expect(document.addEventListener).toHaveBeenCalledTimes(1);

        expect(fake_dropdown_object.removeEventListener).toHaveBeenCalledTimes(2);
        expect(document.removeEventListener).toHaveBeenCalledTimes(1);

        expect(emitter.off).toHaveBeenCalledWith("hide-action-menu", expect.any(Function));
    });
});
