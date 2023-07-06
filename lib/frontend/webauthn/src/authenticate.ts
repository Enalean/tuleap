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
import { postJSON, uri } from "@tuleap/fetch-result";
import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from "@simplewebauthn/typescript-types";
import { errAsync, okAsync, ResultAsync } from "neverthrow";
import { browserSupportsWebAuthn, startAuthentication } from "@simplewebauthn/browser";
import { UserHasNoRegisteredPasskeyFault } from "./faults/UserHasNoRegisteredPasskeyFault";
import { CouldNotCheckRegisteredPasskeysFault } from "./faults/CouldNotCheckRegisteredPasskeysFault";

function beginAuth(): ResultAsync<AuthenticationResponseJSON, Fault> {
    return postJSON<PublicKeyCredentialRequestOptionsJSON>(
        uri`/webauthn/authentication-challenge`,
        {}
    ).andThen((options) => {
        options.timeout = 30_000; // ms

        return ResultAsync.fromPromise(
            startAuthentication(options),
            (error: unknown): Fault =>
                error instanceof Error
                    ? Fault.fromError(error)
                    : Fault.fromMessage("Failed to authenticate with your passkey")
        );
    });
}

export function getAuthenticationResult(): ResultAsync<AuthenticationResponseJSON | null, Fault> {
    return beginAuth()
        .map((response): AuthenticationResponseJSON | null => response)
        .orElse((fault) => {
            if ("isForbidden" in fault && fault.isForbidden()) {
                // 403 is returned by first fetch when user has no key
                // In this case authentication is considered ok
                return okAsync(null);
            }
            return errAsync(fault);
        });
}

export function canUserDoWebAuthn(): ResultAsync<null, Fault> {
    if (!browserSupportsWebAuthn()) {
        // Should not happen as minimum Tuleap support of browsers is above minimum WebAuthn support
        return errAsync(Fault.fromMessage("Your browser does not support WebAuthn"));
    }

    return postJSON<PublicKeyCredentialRequestOptionsJSON>(
        uri`/webauthn/authentication-challenge`,
        {}
    )
        .map(() => null)
        .mapErr((fault) => {
            if ("isForbidden" in fault && fault.isForbidden()) {
                return UserHasNoRegisteredPasskeyFault(fault);
            }
            return CouldNotCheckRegisteredPasskeysFault(fault);
        });
}
