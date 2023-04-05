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

import type { EncodedURI } from "@tuleap/fetch-result";
import { ResponseRetriever } from "@tuleap/fetch-result";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { buildGet } from "./Querier";

export type { GitLabCredentials } from "./Querier";

// Define an unused type alias just so we can import ResultAsync and Fault types for the doc-blocks.
// eslint-disable-next-line @typescript-eslint/no-unused-vars
type _Unused = { a: ResultAsync<never, Fault>; b: EncodedURI };

const response_retriever = ResponseRetriever(window);

/**
 * @param {EncodedURI} uri The URI destination of the request. URI-encoding is handled automatically.
 * @param {GitLabCredentials} credentials The GitLab access token to authenticate the request.
 * @returns {ResultAsync<Response, Fault>}
 */
export const get = buildGet(response_retriever);
