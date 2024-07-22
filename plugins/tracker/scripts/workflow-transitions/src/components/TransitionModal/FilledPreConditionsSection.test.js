/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";

import FilledPreConditionsSection from "./FilledPreConditionsSection.vue";
import { createLocalVueForTests } from "../../support/local-vue.js";

describe("FilledPreConditionsSection", () => {
    it("component is renderer", async () => {
        const wrapper = shallowMount(FilledPreConditionsSection, {
            localVue: await createLocalVueForTests(),
        });
        expect(wrapper).toMatchInlineSnapshot(`
<section class="tlp-modal-body-section">
  <h2 class="tlp-modal-subtitle">Conditions of the transition</h2>
  <authorized-u-groups-select-stub></authorized-u-groups-select-stub>
  <not-empty-fields-select-stub></not-empty-fields-select-stub>
  <comment-not-empty-check-box-stub></comment-not-empty-check-box-stub>
</section>
`);
    });
});
