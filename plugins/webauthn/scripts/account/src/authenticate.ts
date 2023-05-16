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
import type { Fault } from "@tuleap/fault";
import { ResultAsync } from "neverthrow";
import { post, postJSON, uri } from "@tuleap/fetch-result";
import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialRequestOptionsJSON,
} from "@simplewebauthn/typescript-types";
import { startAuthentication } from "@simplewebauthn/browser";
import { AuthenticationFault } from "./CustomFault";

export function authenticate(): ResultAsync<null, Fault> {
    return postJSON<PublicKeyCredentialRequestOptionsJSON>(
        uri`/webauthn/authentication-challenge`,
        {}
    )
        .andThen((options) => {
            options.timeout = 30000; // ms

            return authentication(options);
        })
        .andThen((assertion_response) => {
            return post(uri`/webauthn/authentication`, {}, assertion_response);
        })
        .map(() => null);
}

function authentication(
    options: PublicKeyCredentialRequestOptionsJSON
): ResultAsync<AuthenticationResponseJSON, Fault> {
    return ResultAsync.fromPromise(startAuthentication(options), AuthenticationFault.fromError);
}
