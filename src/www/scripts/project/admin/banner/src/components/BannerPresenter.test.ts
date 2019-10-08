/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { mount } from "@vue/test-utils";
import BannerPresenter from "./BannerPresenter.vue";
import { createProjectAdminBannerLocalVue } from "../helpers/local-vue-for-tests";

describe("BannerPresenter", () => {
    it("displays the raw banner", async () => {
        const banner_message = "<b>My banner content</b>";

        const wrapper = mount(BannerPresenter, {
            localVue: await createProjectAdminBannerLocalVue(),
            propsData: {
                message: banner_message
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
