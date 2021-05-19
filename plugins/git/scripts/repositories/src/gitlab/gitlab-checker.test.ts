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

import { isGitlabRepository, isGitlabRepositoryWellConfigured } from "./gitlab-checker";
import type { Repository } from "../type";

describe("gitlabChecker", () => {
    describe("isGitlabRepository", () => {
        it("When repository hasn't gitlab_data, Then return false", () => {
            expect(isGitlabRepository({} as Repository)).toBeFalsy();
        });
        it("When repository has gitlab_data but null, Then return false", () => {
            const repo = { gitlab_data: null } as Repository;
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but empty, Then return false", () => {
            const repo = { gitlab_data: {} } as Repository;
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only gitlab_repository_url, Then return false", () => {
            const repo = { gitlab_data: { gitlab_repository_url: "example.com" } } as Repository;
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only gitlab_repository_id, Then return false", () => {
            const repo = { gitlab_data: { gitlab_repository_id: 14589 } } as Repository;
            expect(isGitlabRepository(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data, Then return true", () => {
            const repo = {
                gitlab_data: { gitlab_repository_id: 14589, gitlab_repository_url: "example.com" },
            } as Repository;
            expect(isGitlabRepository(repo)).toBeTruthy();
        });
    });

    describe("isGitlabRepositoryWellConfigured", () => {
        it("When repository hasn't gitlab_data, Then return false", () => {
            expect(isGitlabRepositoryWellConfigured({} as Repository)).toBeFalsy();
        });
        it("When repository has gitlab_data but null, Then return false", () => {
            const repo = { gitlab_data: null } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but empty, Then return false", () => {
            const repo = { gitlab_data: {} } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only gitlab_repository_url, Then return false", () => {
            const repo = { gitlab_data: { gitlab_repository_url: "example.com" } } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only gitlab_repository_id, Then return false", () => {
            const repo = { gitlab_data: { gitlab_repository_id: 14589 } } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data but only is_webhook_configured, Then return false", () => {
            const repo = { gitlab_data: { is_webhook_configured: true } } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeFalsy();
        });
        it("When repository has gitlab_data and webhook is configured, Then return true", () => {
            const repo = {
                gitlab_data: {
                    gitlab_repository_id: 14589,
                    gitlab_repository_url: "example.com",
                    is_webhook_configured: true,
                },
            } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeTruthy();
        });
        it("When repository has gitlab_data webhook is not configured, Then return false", () => {
            const repo = {
                gitlab_data: {
                    gitlab_repository_id: 14589,
                    gitlab_repository_url: "example.com",
                    is_webhook_configured: false,
                },
            } as Repository;
            expect(isGitlabRepositoryWellConfigured(repo)).toBeFalsy();
        });
    });
});
