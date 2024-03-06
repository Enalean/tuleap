/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { buildHomepageUrl } from "./homepage-url-builder";

describe("homepage-url-builder", () => {
    it("should build the url to the pull-requests homepage app", () => {
        const location: Location = {
            origin: "https://www.example.com",
        } as unknown as Location;

        const project_id = 101;
        const repository_id = 1;
        const url = buildHomepageUrl(location, project_id, repository_id);

        expect(url.toString()).toBe(
            `https://www.example.com/plugins/git/?action=pull-requests&repo_id=${repository_id}&group_id=${project_id}&tab=homepage`,
        );
    });
});
