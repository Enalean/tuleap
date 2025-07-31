/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import EditCell from "./EditCell.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { DASHBOARD_ID, DASHBOARD_TYPE } from "../../injection-symbols";
import { PROJECT_DASHBOARD, USER_DASHBOARD } from "../../domain/DashboardType";

describe(`EditCell`, () => {
    const getWrapper = (
        uri: string,
        even: boolean,
        dashboard_type: string,
    ): VueWrapper<InstanceType<typeof EditCell>> => {
        return shallowMount(EditCell, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [DASHBOARD_TYPE.valueOf()]: dashboard_type,
                    [DASHBOARD_ID.valueOf()]: 22,
                },
            },
            props: { uri, even },
        });
    };

    it(`renders a link to artifact URI who will redirect user on project dashboard at artifact update`, () => {
        const uri = "/plugins/tracker/?aid=77";
        const wrapper = getWrapper(uri, false, PROJECT_DASHBOARD);

        expect(wrapper.get("a").attributes("href")).toBe(`${uri}&project-dashboard-id=22`);
    });

    it(`renders a link to artifact URI who will redirect user on user dashboard at artifact update`, () => {
        const uri = "/plugins/tracker/?aid=77";
        const wrapper = getWrapper(uri, false, USER_DASHBOARD);

        expect(wrapper.get("a").attributes("href")).toBe(`${uri}&my-dashboard-id=22`);
    });
});
