/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { isRepositoryHandledByGerrit } from "./gerrit-checker";
import type { Repository, FormattedGitLabRepository } from "../type";

describe("gitlabChecker", () => {
    describe("isRepositoryHandledByGerrit", () => {
        it("When repository does not have server (comes from GitLab), Then return false", () => {
            const repo = {} as FormattedGitLabRepository;
            expect(isRepositoryHandledByGerrit(repo)).toBeFalsy();
        });
        it("When repository has server but null, Then return false", () => {
            const repo = { server: null } as Repository;
            expect(isRepositoryHandledByGerrit(repo)).toBeFalsy();
        });
        it("When repository has server, Then return true", () => {
            const repo = {
                server: { id: 14589, html_url: "example.com" },
            } as Repository;
            expect(isRepositoryHandledByGerrit(repo)).toBeTruthy();
        });
    });
});
