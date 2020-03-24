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

import { MilestoneData } from "../type";
import { is_testmanagement_activated } from "./test-management-helper";

describe("TestManagementHelper", () => {
    it("When there is a pane TTM, Then true is returned", () => {
        const release_data: MilestoneData = {
            resources: {
                additional_panes: [
                    {
                        uri: "plugins/testmanagement",
                        title: "Test Management",
                        identifier: "testmgmt",
                        icon_name: "fa-external",
                    },
                ],
            },
        } as MilestoneData;

        expect(is_testmanagement_activated(release_data)).toBe(true);
    });

    it("When TTM pane is not included, Then false is returned", () => {
        const release_data: MilestoneData = {
            resources: {
                additional_panes: [
                    {
                        uri: "plugins/taskboard",
                        title: "Taskboard",
                        identifier: "taskboard",
                        icon_name: "fa-external",
                    },
                ],
            },
        } as MilestoneData;

        expect(is_testmanagement_activated(release_data)).toBe(false);
    });
});
