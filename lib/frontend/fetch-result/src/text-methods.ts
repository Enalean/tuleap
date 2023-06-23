/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import type { RetrieveResponse } from "./ResponseRetriever";
import type { PostMethod } from "./constants";
import { getURI } from "./auto-encoder";
import type { EncodedURI } from "./uri-string-template";
import { credentials } from "./headers";
import type { Fault } from "@tuleap/fault";
import { TextErrorHandler } from "./faults/TextErrorHandler";
import { decodeAsText } from "./text-decoder";

export const buildSendFormAndReceiveText =
    (response_retriever: RetrieveResponse, method: PostMethod) =>
    (uri: EncodedURI, payload: FormData): ResultAsync<string, Fault> =>
        response_retriever
            .retrieveResponse(getURI(uri), { method, credentials, body: payload })
            .andThen(TextErrorHandler().handleErrorResponse)
            .andThen(decodeAsText);
