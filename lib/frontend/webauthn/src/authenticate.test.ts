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
import type { PublicKeyCredentialRequestOptionsJSON } from "@simplewebauthn/typescript-types";
import * as simplewebauthn from "@simplewebauthn/browser";
import * as fetch_result from "@tuleap/fetch-result";
import { errAsync, okAsync } from "neverthrow";
import { Fault, isFault } from "@tuleap/fault";
import { TuleapAPIFault } from "../tests/stubs/TuleapAPIFault";
import { AuthenticationResponseJSONStub } from "../tests/stubs/AuthenticationResponseJSONStub";
import { authenticate, canUserDoWebAuthn, getAuthenticationResult } from "./authenticate";

vi.mock("@simplewebauthn/browser");
vi.mock("@tuleap/fetch-result");

const isUserHasNoRegisteredPasskey = (fault: Fault): boolean =>
    "isUserHasNoRegisteredPasskey" in fault && fault.isUserHasNoRegisteredPasskey() === true;
const isCouldNotCheckRegisteredPasskeys = (fault: Fault): boolean =>
    "isCouldNotCheckRegisteredPasskeys" in fault &&
    fault.isCouldNotCheckRegisteredPasskeys() === true;

describe("authenticate", () => {
    describe(`authenticate()`, () => {
        it("returns Ok with null when browser does not support WebAuthn", async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(false);

            const result = await authenticate();
            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(false)).toBeNull();
        });

        it("returns Err if the call to authentication-challenge returns neither 200 nor 403", async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                errAsync(Fault.fromMessage("401 Unauthorized"))
            );

            expect((await authenticate()).isErr()).toBe(true);
        });

        it(`when user has no registered passkey
            and the call to authentication-challenge returns Forbidden error code,
            it will return Ok with null`, async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                errAsync(TuleapAPIFault.fromCodeAndMessage(403, "Forbidden"))
            );

            const result = await authenticate();
            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(false)).toBeNull();
        });

        it("returns Err when passkey authentication failed", async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({} as PublicKeyCredentialRequestOptionsJSON)
            );
            vi.spyOn(simplewebauthn, "startAuthentication").mockRejectedValue(new Error("failed"));

            expect((await authenticate()).isErr()).toBe(true);
        });

        it("returns Err when the call to authentication endpoint failed", async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({} as PublicKeyCredentialRequestOptionsJSON)
            );
            vi.spyOn(simplewebauthn, "startAuthentication").mockResolvedValue(
                AuthenticationResponseJSONStub()
            );
            vi.spyOn(fetch_result, "post").mockReturnValue(
                errAsync(TuleapAPIFault.fromCodeAndMessage(400, "Bad Request"))
            );

            expect((await authenticate()).isErr()).toBe(true);
        });

        it("returns Ok with null when the call to authentication endpoint succeeded", async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(okAsync({}));
            vi.spyOn(simplewebauthn, "startAuthentication").mockResolvedValue(
                AuthenticationResponseJSONStub()
            );
            vi.spyOn(fetch_result, "post").mockReturnValue(
                okAsync(new Response("", { status: 200, statusText: "OK" }))
            );

            const result = await authenticate();
            expect(result.isOk()).toBe(true);
            expect(result.unwrapOr(false)).toBeNull();
        });
    });

    describe(`canUserDoWebAuthn()`, () => {
        it(`returns Ok with null`, async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({} as PublicKeyCredentialRequestOptionsJSON)
            );

            const result = await canUserDoWebAuthn();
            expect(result.unwrapOr(false)).toBeNull();
        });

        it(`returns a Fault if the browser does not support WebAuthn feature`, async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(false);

            const result = await canUserDoWebAuthn();
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isFault(result.error)).toBe(true);
        });

        it(`when user has no registered passkey
            and the call to authentication-challenge returns Forbidden error code,
            it will return a special Fault`, async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                errAsync(TuleapAPIFault.fromCodeAndMessage(403, "Forbidden"))
            );

            const result = await canUserDoWebAuthn();
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isUserHasNoRegisteredPasskey(result.error)).toBe(true);
        });

        it(`returns a special Fault when the call to authentication-challenge returns another error`, async () => {
            vi.spyOn(simplewebauthn, "browserSupportsWebAuthn").mockReturnValue(true);
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                errAsync(Fault.fromMessage("Network error"))
            );

            const result = await canUserDoWebAuthn();
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isCouldNotCheckRegisteredPasskeys(result.error)).toBe(true);
        });
    });

    describe(`getAuthenticationResult()`, () => {
        it(`returns Ok with the authentication response JSON
            so that caller can send it to Tuleap API for verification
            and sets timeout option to 30 seconds`, async () => {
            const EXPECTED_TIMEOUT_IN_MILLISECONDS = 30_000;
            const response_JSON = AuthenticationResponseJSONStub();
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({} as PublicKeyCredentialRequestOptionsJSON)
            );
            const startAuthentication = vi
                .spyOn(simplewebauthn, "startAuthentication")
                .mockResolvedValue(response_JSON);

            const result = await getAuthenticationResult();
            expect(result.unwrapOr(null)).toBe(response_JSON);
            const options = startAuthentication.mock.calls[0][0];
            expect(options.timeout).toBe(EXPECTED_TIMEOUT_IN_MILLISECONDS);
        });

        it(`when user has no registered passkey
            and the call to authentication-challenge returns Forbidden error code,
            it will return Ok`, async () => {
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                errAsync(TuleapAPIFault.fromCodeAndMessage(403, "Forbidden"))
            );

            const result = await getAuthenticationResult();
            expect(result.unwrapOr(false)).toBeNull();
        });

        it(`returns Err when the call to authentication-challenge returns an error`, async () => {
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                errAsync(Fault.fromMessage("Ooops"))
            );

            const result = await getAuthenticationResult();
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isFault(result.error)).toBe(true);
        });

        it(`returns Err when passkey authentication failed`, async () => {
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(
                okAsync({} as PublicKeyCredentialRequestOptionsJSON)
            );
            vi.spyOn(simplewebauthn, "startAuthentication").mockRejectedValue(new Error("failed"));

            const result = await getAuthenticationResult();
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isFault(result.error)).toBe(true);
        });
    });
});
