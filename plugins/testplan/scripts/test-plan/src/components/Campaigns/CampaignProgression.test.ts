/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import CampaignProgression from "./CampaignProgression.vue";
import { Campaign } from "../../type";
import { createTestPlanLocalVue } from "../../helpers/local-vue-for-test";

describe("CampaignProgression", () => {
    it("Displays a campaign as a card", async () => {
        const wrapper = shallowMount(CampaignProgression, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                campaign: {
                    label: "My campaign",
                    nb_of_blocked: 1,
                    nb_of_failed: 2,
                    nb_of_notrun: 34,
                    nb_of_passed: 10,
                } as Campaign,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
