/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { buildVueOverviewURL } from "./vue-overview-url-builder";

describe("vue-overview-url-builder", () => {
    it("Given a pull-request, then it should build an url to the view overview app", () => {
        const location: Location = {
            origin: "https://www.example.com",
        } as unknown as Location;

        const url = buildVueOverviewURL(
            location,
            {
                id: 15,
                repository: {
                    id: 2,
                    project: {
                        id: 105,
                        icon: "",
                        label: "R&D stuff",
                        uri: "uri/to/project",
                    },
                },
            },
            101,
            1
        );

        expect(url.toString()).toBe(
            "https://www.example.com/plugins/git/?action=pull-requests&repo_id=1&group_id=101&tab=overview#/pull-requests/15/overview"
        );
    });
});
