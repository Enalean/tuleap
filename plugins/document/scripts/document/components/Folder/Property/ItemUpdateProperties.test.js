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
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";
import ItemUpdateProperties from "./ItemUpdateProperties.vue";

describe("ItemUpdateProperties", () => {
    let file_properties_update_factory;
    beforeEach(() => {
        file_properties_update_factory = (item = {}) => {
            return shallowMount(ItemUpdateProperties, {
                localVue,
                propsData: {
                    version: {
                        title: "Not idea",
                        is_file_locked: false,
                        changelog: "",
                    },
                    item: { ...item },
                },
            });
        };
    });
    describe("ApprovalUpdateProperties", () => {
        it("displays the approvals option action for update if the item has an approval table regardless of approval enable status", () => {
            const wrapper = file_properties_update_factory({ has_approval_table: true });

            expect(wrapper.contains("[data-test='update-approval-properties']")).toBeTruthy();
        });
        it("does not display the approvals option action for update if the item has no approval table", () => {
            const wrapper = file_properties_update_factory({ has_approval_table: false });

            expect(wrapper.contains("[data-test='update-approval-properties']")).toBeFalsy();
        });
        it(`Given an action event thrown by my child component (MyUltraCoolEvent)
            Then it resend the received event`, () => {
            const wrapper = file_properties_update_factory();

            wrapper.vm.$emit("approvalTableActionChange", "MyUltraCoolEvent");

            expect(wrapper.emitted().approvalTableActionChange[0]).toEqual(["MyUltraCoolEvent"]);
        });
    });
});
