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

import { describe, it, expect } from "vitest";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { GitLabErrorHandler } from "./GitLabErrorHandler";

const isAPIFault = (fault: Fault): boolean =>
    "isGitLabAPIFault" in fault && fault.isGitLabAPIFault() === true;

describe(`GitLabErrorHandler`, () => {
    const handle = (response: Response): ResultAsync<Response, Fault> => {
        const handler = GitLabErrorHandler();
        return handler.handleErrorResponse(response);
    };

    it(`when response is ok, it returns the response`, async () => {
        const response = { ok: true } as Response;

        const result = await handle(response);
        if (!result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(result.value).toBe(response);
    });

    it(`when response is not ok, it returns a GitlabAPIFault`, async () => {
        const response = { ok: false, status: 403 } as Response;

        const result = await handle(response);
        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        expect(isAPIFault(result.error)).toBe(true);
    });
});
