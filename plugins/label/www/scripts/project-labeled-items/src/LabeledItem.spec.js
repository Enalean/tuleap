/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import Vue from "vue";
import LabeledItem from "./LabeledItem.vue";

describe("LabeledItem", function() {
    it("Given a svg icon, then it should purify it.", function() {
        const LabeledItemVueElement = Vue.extend(LabeledItem);

        const vm = new LabeledItemVueElement({
            propsData: {
                item: {
                    small_icon: "<svg><g/onload=alert(2)//<p>"
                }
            }
        });

        vm.$mount();

        const labeled_item_icon = vm.$el.querySelector(".labeled-item-icon").innerHTML;
        const expected_sanitized_svg = "<svg><g></g></svg>";

        expect(labeled_item_icon).toEqual(expected_sanitized_svg);
    });
});
