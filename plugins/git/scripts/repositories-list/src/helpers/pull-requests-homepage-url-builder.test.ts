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

import { describe, it, expect, beforeEach } from "@jest/globals";
import {
    getOldPullRequestsDashboardUrl,
    getPullRequestsHomepageUrl,
} from "./pull-requests-homepage-url-builder";

const project_id = 101;
const repository_id = 5;
const origin = "https://www.example.com";

describe("pull-requests-homepage-url-builder", () => {
    let location: Location;

    beforeEach(() => {
        location = { origin } as unknown as Location;
    });

    it("getOldPullRequestsDashboardUrl() should return an url pointing to the old pull-requests dashboard", () => {
        const url = getOldPullRequestsDashboardUrl(location, project_id, repository_id);

        expect(url.toString()).toBe(
            `${origin}/plugins/git/?action=pull-requests&group_id=${project_id}&repo_id=${repository_id}#/dashboard`,
        );
    });

    it("getPullRequestsHomepageUrl() should return an url pointing to the new pull-requests homepage", () => {
        const url = getPullRequestsHomepageUrl(location, project_id, repository_id);

        expect(url.toString()).toBe(
            `${origin}/plugins/git/?action=pull-requests&group_id=${project_id}&repo_id=${repository_id}&tab=homepage`,
        );
    });
});
