/**
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { isGitlabRepository } from "./gitlab-checker";

describe("gitlabChecker", () => {
    describe("isGitlabRepository", () => {
        it("When repository hasn't gitlab_data, Then return false", () => {
            expect(isGitlabRepository({})).toBeFalsy();
        });
        it("When repository has gitlab_data but null, Then return false", () => {
            const repo = { gitlab_data: null };
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but empty, Then return false", () => {
            const repo = { gitlab_data: {} };
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only full_url, Then return false", () => {
            const repo = { gitlab_data: { full_url: "example.com" } };
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only gitlab_id, Then return false", () => {
            const repo = { gitlab_data: { gitlab_id: 14589 } };
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data, Then return true", () => {
            const repo = { gitlab_data: { gitlab_id: 14589, full_url: "example.com" } };
            expect(isGitlabRepository(repo)).toBeTruthy();
        });
    });
});
