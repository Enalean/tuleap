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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

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

import localVue from "../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import ObsolescenceDateMetadata from "./ObsolescenceDateMetadata.vue";

describe("ObsolescenceDateMetadata", () => {
    let obsolescence_date_factory;
    beforeEach(() => {
        obsolescence_date_factory = () => {
            return shallowMount(ObsolescenceDateMetadata, {
                localVue
            });
        };
    });
    it(`Given an obsolescence date
        When the user creating a item
        Then it raise the 'documentObsolescenceDateSelectEvent' event with the value of the selected date'`, () => {
        const wrapper = obsolescence_date_factory();

        const date_input = wrapper.find("[data-test=document-new-item-obsolescence-date]");

        date_input.element.value = "2019-23-04";
        date_input.trigger("click");

        expect(wrapper.emitted().documentObsolescenceDateSelectEvent[0]).toEqual(["2019-23-04"]);
    });
});
