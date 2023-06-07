/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { GitlabApi } from "../../api/gitlab-api-querier";
import type { GitlabCredentials, GitlabGroup } from "../../stores/types";

export interface GitlabApiQuerierStub extends GitlabApi {
    getUsedCredentials: () => GitlabCredentials | null;
    getCallsNumber: () => number;
}

export const GitlabApiQuerierStub = {
    withGitlabGroups: (groups: GitlabGroup[]): GitlabApiQuerierStub => {
        let used_credentials: GitlabCredentials | null = null,
            calls = 0;

        return {
            getGitlabGroups(credentials): ResultAsync<readonly GitlabGroup[], Fault> {
                used_credentials = credentials;
                calls++;
                return okAsync(groups);
            },
            getUsedCredentials(): GitlabCredentials | null {
                return used_credentials;
            },
            getCallsNumber(): number {
                return calls;
            },
        };
    },

    withFault: (fault: Fault): GitlabApi => ({
        getGitlabGroups: (): ResultAsync<readonly GitlabGroup[], Fault> => errAsync(fault),
    }),
};
