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
import type { Campaign } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("CampaignProgression", () => {
    it("Displays a campaign as a card", () => {
        const wrapper = shallowMount(CampaignProgression, {
            props: {
                campaign: {
                    label: "My campaign",
                    nb_of_blocked: 1,
                    nb_of_failed: 2,
                    nb_of_notrun: 34,
                    nb_of_passed: 10,
                } as Campaign,
            },
            global: { ...getGlobalTestOptions({}) },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Display a progress bar even if there is no test in the campaign", () => {
        const wrapper = shallowMount(CampaignProgression, {
            props: {
                campaign: {
                    label: "My campaign",
                    nb_of_blocked: 0,
                    nb_of_failed: 0,
                    nb_of_notrun: 0,
                    nb_of_passed: 0,
                } as Campaign,
            },
            global: { ...getGlobalTestOptions({}) },
        });

        const progress = wrapper.find("[data-test=progress-not-run]");
        expect(progress.classes("test-plan-campaign-progression-width-100")).toBe(true);
        expect(progress.text()).toBe("0");
    });
});
