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

import { describe, it, expect } from "vitest";
import {
    buildBaseUrl,
    buildFilesTabUrl,
    buildOverviewTabUrl,
    buildCommitsTabUrl,
} from "./base-url-builders";

const pull_request_id = 1;
const repository_id = "2";
const project_id = "105";
const location = {
    origin: "https://example.com",
} as Location;

describe("base-url-builders", () => {
    it("should build the base url", () => {
        expect(buildBaseUrl(location, repository_id, project_id).toString()).toBe(
            `https://example.com/plugins/git/?action=pull-requests&repo_id=${repository_id}&group_id=${project_id}`,
        );
    });

    it("should build the commits app url", () => {
        const base_url = buildBaseUrl(location, repository_id, project_id);

        expect(buildCommitsTabUrl(base_url).toString()).toBe(
            `https://example.com/plugins/git/?action=pull-requests&repo_id=${repository_id}&group_id=${project_id}&tab=commits`,
        );
    });

    it("should build the overview app url", () => {
        const base_url = buildBaseUrl(location, repository_id, project_id);

        expect(buildOverviewTabUrl(base_url, pull_request_id).toString()).toBe(
            `https://example.com/plugins/git/?action=pull-requests&repo_id=${repository_id}&group_id=${project_id}&tab=overview#/pull-requests/${pull_request_id}/overview`,
        );
    });

    it("should build the files changes app url", () => {
        const base_url = buildBaseUrl(location, repository_id, project_id);

        expect(buildFilesTabUrl(base_url, pull_request_id).toString()).toBe(
            `https://example.com/plugins/git/?action=pull-requests&repo_id=${repository_id}&group_id=${project_id}#/pull-requests/${pull_request_id}/files`,
        );
    });
});
