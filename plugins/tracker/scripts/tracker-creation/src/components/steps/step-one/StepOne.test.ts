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

import StepOne from "./StepOne.vue";
import type { VueWrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import type { ProjectTemplate, State, Tracker } from "../../../store/type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";

describe("StepOne", () => {
    let mock_set_slugify_shortname_mode: jest.Mock,
        company_name: string,
        project_templates: ProjectTemplate[];
    beforeEach(() => {
        mock_set_slugify_shortname_mode = jest.fn();
        company_name = "";
        project_templates = [];
    });

    function getWrapper(): VueWrapper {
        const default_templates: Tracker[] = [];

        return mount(StepOne, {
            shallow: true,
            global: {
                ...getGlobalTestOptions({
                    state: {
                        default_templates,
                        project_templates,
                        company_name,
                    } as State,
                    mutations: {
                        setSlugifyShortnameMode: mock_set_slugify_shortname_mode,
                    },
                }),
                stubs: {
                    StepLayout: false,
                },
            },
        });
    }

    it("resets the slugify mode when it is mounted", () => {
        getWrapper();

        expect(mock_set_slugify_shortname_mode).toHaveBeenCalledWith(expect.anything(), true);
    });

    it(`displays the company name if the platform name is not Tuleap`, () => {
        project_templates = [{ project_name: "Trionychoideachid", tracker_list: [] }];
        company_name = "Nichya company";
        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=platform-template-name]").text()).toBe(
            "Nichya company templates",
        );
    });

    it(`displays 'Custom templates' if the platform name is Tuleap`, () => {
        project_templates = [{ project_name: "moment", tracker_list: [] }];
        company_name = "Tuleap";

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=platform-template-name]").text()).toBe("Custom templates");
    });

    it(`Does not display custom template block if there is no project_templates`, () => {
        project_templates = [];
        company_name = "Tuleap";

        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=platform-template-name]").exists()).toBe(false);
    });
});
