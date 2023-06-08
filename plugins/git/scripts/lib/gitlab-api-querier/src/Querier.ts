/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { RetrieveResponse, EncodedURI } from "@tuleap/fetch-result";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { GitLabErrorHandler } from "./GitLabErrorHandler";

export type GitLabCredentials = {
    readonly token: string;
};

export const buildGet =
    (response_retriever: RetrieveResponse) =>
    (uri: EncodedURI, credentials: GitLabCredentials): ResultAsync<Response, Fault> =>
        response_retriever
            .retrieveResponse(uri, {
                headers: new Headers({ Authorization: "Bearer " + credentials.token }),
                method: "GET",
                mode: "cors",
            })
            .andThen(GitLabErrorHandler().handleErrorResponse);
