/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import GitlabGroupLinkWizard from "./GitlabGroupLinkWizard.vue";
import { STEP_GITLAB_GROUP } from "../types";
import { getGlobalTestOptions } from "../tests/helpers/global-options-for-tests";

describe("GitlabGroupLinkWizard", () => {
    it("Given the id of the active step, Then it will render the steps with the correct classes (step-previous, step-current, step-next)", () => {
        const wrapper = shallowMount(GitlabGroupLinkWizard, {
            props: {
                active_step_id: STEP_GITLAB_GROUP,
            },
            global: {
                ...getGlobalTestOptions({}),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
