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

import { describe, expect, it } from "vitest";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { buildGet } from "./Querier";
import { RetrieveResponseStub } from "./RetrieveResponseStub";
import { uri } from "@tuleap/fetch-result";

const URI = "https://gitlab.example.com/api/v4/projects/91";
const TOKEN = "4Twg0PGv61QYqy";

describe(`Querier`, () => {
    let response_retriever: RetrieveResponseStub;
    describe(`get()`, () => {
        const getResponse = (): ResultAsync<Response, Fault> => {
            return buildGet(response_retriever)(uri`${URI}`, { token: TOKEN });
        };

        it(`makes a query to the given GitLab URI with the given credentials token
            in cross-origin mode and returns the response`, async () => {
            const response = { ok: true } as Response;
            response_retriever = RetrieveResponseStub.withResponse(response);

            const result = await getResponse();
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            const options = response_retriever.getOptions();
            if (options === null) {
                throw Error("Expected options to be given to ResponseRetriever");
            }
            expect(options.method).toBe("GET");
            if (!options.headers) {
                throw Error("Expected headers to be given to ResponseRetriever");
            }
            expect(options.headers.get("Authorization")).toBe("Bearer " + TOKEN);
            expect(options.mode).toBe("cors");
        });
    });
});
