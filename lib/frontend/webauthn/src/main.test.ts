/**
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
import { describe, expect, it, vi } from "vitest";
import { authenticate } from "./main";
import * as simplewebauthn from "@simplewebauthn/browser";
import * as fetch_result from "@tuleap/fetch-result";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { TuleapAPIFault } from "../tests/stubs/TuleapAPIFault";
import { authenticationResponseJSONStub } from "../tests/stubs/AuthenticationResponseJSON";

vi.mock("@simplewebauthn/browser");
vi.mock("@tuleap/fetch-result");

describe("authenticate", () => {
    it("returns Ok when not support webauthn", async () => {
        vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(false);

        const result = await authenticate();
        expect(result.isOk()).toBe(true);
        expect(result.unwrapOr(false)).toBeNull();
    });

    it("returns Err if first fetch not returns 200 nor 403", async () => {
        vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
        vi.spyOn(fetch_result, "postJSON").mockReturnValue(
            errAsync(Fault.fromMessage("401 Unauthorized"))
        );

        expect((await authenticate()).isErr()).toBe(true);
    });

    it("returns Ok if first fetch returns 403", async () => {
        vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
        vi.spyOn(fetch_result, "postJSON").mockReturnValue(
            errAsync(TuleapAPIFault.fromCodeAndMessage(403, "Forbidden"))
        );

        const result = await authenticate();
        expect(result.isOk()).toBe(true);
        expect(result.unwrapOr(false)).toBeNull();
    });

    it("returns Err if startAuthentication failed", async () => {
        vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
        vi.spyOn(fetch_result, "postJSON").mockReturnValue(okAsync({}));
        vi.spyOn(simplewebauthn, "startAuthentication").mockRejectedValue(new Error("failed"));

        expect((await authenticate()).isErr()).toBe(true);
    });

    it("returns Err is second fetch failed", async () => {
        vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
        vi.spyOn(fetch_result, "postJSON").mockReturnValue(okAsync({}));
        vi.spyOn(simplewebauthn, "startAuthentication").mockResolvedValue(
            authenticationResponseJSONStub()
        );
        vi.spyOn(fetch_result, "post").mockReturnValue(
            errAsync(TuleapAPIFault.fromCodeAndMessage(400, "Bad Request"))
        );

        expect((await authenticate()).isErr()).toBe(true);
    });

    it("returns Ok if second fetch succeed", async () => {
        vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
        vi.spyOn(fetch_result, "postJSON").mockReturnValue(okAsync({}));
        vi.spyOn(simplewebauthn, "startAuthentication").mockResolvedValue(
            authenticationResponseJSONStub()
        );
        vi.spyOn(fetch_result, "post").mockReturnValue(
            okAsync(new Response("", { status: 200, statusText: "OK" }))
        );

        const result = await authenticate();
        expect(result.isOk()).toBe(true);
        expect(result.unwrapOr(false)).toBeNull();
    });
});
