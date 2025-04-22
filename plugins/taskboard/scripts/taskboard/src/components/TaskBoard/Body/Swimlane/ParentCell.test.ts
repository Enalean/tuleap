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

import { shallowMount, type VueWrapper } from "@vue/test-utils";
import ParentCell from "./ParentCell.vue";
import type { Swimlane } from "../../../../type";

describe("ParentCell", () => {
    function getWrapper(swimlane: Swimlane): VueWrapper<InstanceType<typeof ParentCell>> {
        return shallowMount(ParentCell, {
            props: { swimlane },
        });
    }

    it("renders the parent card when it has children", () => {
        const wrapper = getWrapper({
            card: { id: 43, has_children: true },
        } as Swimlane);

        expect(wrapper.vm.should_no_mapping_message_be_displayed).toBe(false);
        expect(wrapper.vm.edit_mode_class).toStrictEqual([]);
    });

    it("renders the no mapping message when the card has no children", () => {
        const wrapper = getWrapper({
            card: { id: 43, has_children: false },
        } as Swimlane);

        expect(wrapper.vm.should_no_mapping_message_be_displayed).toBe(true);
        expect(wrapper.vm.edit_mode_class).toStrictEqual(["taskboard-cell-parent-card-no-mapping"]);
    });

    it("adds an 'edit-mode' class when the card is being edited", () => {
        const wrapper = getWrapper({
            card: { id: 43, has_children: false, is_in_edit_mode: true },
        } as Swimlane);

        expect(wrapper.vm.edit_mode_class).toStrictEqual([
            "taskboard-cell-parent-card-no-mapping",
            "taskboard-cell-parent-card-edit-mode",
        ]);
    });
});
