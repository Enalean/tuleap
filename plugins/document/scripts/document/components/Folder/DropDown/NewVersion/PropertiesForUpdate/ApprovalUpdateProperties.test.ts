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
import ApprovalUpdateProperties from "./ApprovalUpdateProperties.vue";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";

describe("ApprovalUpdateProperties", () => {
    function instantiateComponent(): VueWrapper<InstanceType<typeof ApprovalUpdateProperties>> {
        return shallowMount(ApprovalUpdateProperties, { global: { ...getGlobalTestOptions({}) } });
    }

    it(`Given the copy action of an approval table
        When the user updating an item
        Then it raise the 'action' event with the value 'copy'`, () => {
        const wrapper = instantiateComponent();

        const radio_input = wrapper.get(
            'input[id="document-new-file-upload-approval-table-action-copy"]',
        );
        radio_input.setChecked();

        const emitted = wrapper.emitted()["approval-table-action-change"];
        if (!emitted) {
            throw new Error("Event has not been emitted");
        }

        expect(emitted[0]).toEqual(["copy"]);
    });
    it(`Given the reset action of an approval table
        When the user updating an item
        Then it raise the 'action' event with the value 'reset'`, () => {
        const wrapper = instantiateComponent();

        const radio_input = wrapper.get(
            'input[id="document-new-file-upload-approval-table-action-reset"]',
        );
        radio_input.setChecked();

        const emitted = wrapper.emitted()["approval-table-action-change"];
        if (!emitted) {
            throw new Error("Event has not been emitted");
        }

        expect(emitted[0]).toEqual(["reset"]);
    });
    it(`Given the empty action of an approval table
        When the user updating an item
        Then it raise the 'action' event with the value 'empty'`, () => {
        const wrapper = instantiateComponent();

        const radio_input = wrapper.get(
            'input[id="document-new-file-upload-approval-table-action-empty"]',
        );
        radio_input.setChecked();

        const emitted = wrapper.emitted()["approval-table-action-change"];
        if (!emitted) {
            throw new Error("Event has not been emitted");
        }

        expect(emitted[0]).toEqual(["empty"]);
    });
});
