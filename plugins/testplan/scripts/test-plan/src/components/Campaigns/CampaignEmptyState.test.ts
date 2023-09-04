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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CampaignEmptyState from "./CampaignEmptyState.vue";
import type { RootState } from "../../store/type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("CampaignEmptyState", () => {
    function createWrapper(
        user_can_create_campaign: boolean,
        show_create_modal = jest.fn(),
    ): VueWrapper<InstanceType<typeof CampaignEmptyState>> {
        return shallowMount(CampaignEmptyState, {
            props: {
                show_create_modal,
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        user_can_create_campaign,
                    } as RootState,
                }),
            },
        });
    }

    it("Displays empty state with new campaign creation button", () => {
        const wrapper = createWrapper(true);

        expect(wrapper.element).toMatchSnapshot();
        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(true);
    });

    it("Does not show the new campaign creation button in the empty state when the user cannot create new ones", () => {
        const wrapper = createWrapper(false);

        expect(wrapper.find("[data-test=new-campaign]").exists()).toBe(false);
    });

    it(`On click on the button, it calls showCreateModal`, async () => {
        const show_create_modal = jest.fn();

        const wrapper = createWrapper(true, show_create_modal);

        await wrapper.find("[data-test=new-campaign]").trigger("click");

        expect(show_create_modal).toHaveBeenCalled();
    });
});
