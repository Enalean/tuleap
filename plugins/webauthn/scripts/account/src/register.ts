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

import { post, postJSON, uri } from "@tuleap/fetch-result";
import type {
    PublicKeyCredentialCreationOptionsJSON,
    RegistrationResponseJSON,
} from "@simplewebauthn/typescript-types";
import { ResultAsync } from "neverthrow";
import { startRegistration } from "@simplewebauthn/browser";
import { RegistrationFault } from "./CustomFault";
import type { Fault } from "@tuleap/fault";

export function register(name: string): ResultAsync<null, Fault> {
    return postJSON<PublicKeyCredentialCreationOptionsJSON>(
        uri`/webauthn/registration-challenge`,
        {}
    )
        .andThen((options) => {
            options.timeout = 30000; // ms

            return registration(options);
        })
        .andThen((attestation_response) => {
            return post(
                uri`/webauthn/registration`,
                {},
                {
                    name: name,
                    response: attestation_response,
                }
            );
        })
        .map(() => null);
}

function registration(
    options: PublicKeyCredentialCreationOptionsJSON
): ResultAsync<RegistrationResponseJSON, Fault> {
    return ResultAsync.fromPromise(startRegistration(options), RegistrationFault.fromError);
}
