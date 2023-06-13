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
import { Fault } from "@tuleap/fault";
import { post, postJSON, uri } from "@tuleap/fetch-result";
import type { PublicKeyCredentialRequestOptionsJSON } from "@simplewebauthn/typescript-types";
import { errAsync, okAsync, ResultAsync } from "neverthrow";
import { browserSupportsWebAuthn, startAuthentication } from "@simplewebauthn/browser";

export function authenticate(): ResultAsync<null, Fault> {
    if (!browserSupportsWebAuthn()) {
        return okAsync(null);
    }

    return postJSON<PublicKeyCredentialRequestOptionsJSON>(
        uri`/webauthn/authentication-challenge`,
        {}
    )
        .andThen((options) => {
            options.timeout = 30_000; // ms

            return ResultAsync.fromPromise(
                startAuthentication(options),
                (error: unknown): Fault =>
                    error instanceof Error
                        ? Fault.fromError(error)
                        : Fault.fromMessage("Failed to authenticate with your passkey")
            );
        })
        .andThen((assertion_response) =>
            post(uri`/webauthn/authentication`, {}, assertion_response)
        )
        .map(() => null)
        .orElse((fault) => {
            if ("isForbidden" in fault && fault.isForbidden()) {
                // 403 is returned by first fetch when user has no key
                // In this case authentication is considered ok
                return okAsync(null);
            }
            return errAsync(fault);
        });
}
