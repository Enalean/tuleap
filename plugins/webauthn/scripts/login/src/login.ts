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
import "../themes/style.scss";
import { selectOrThrow } from "@tuleap/dom";
import { postJSON, uri } from "@tuleap/fetch-result";
import type { PublicKeyCredentialRequestOptionsJSON } from "@simplewebauthn/typescript-types";
import { ResultAsync } from "neverthrow";
import { browserSupportsWebAuthn, startAuthentication } from "@simplewebauthn/browser";
import { Fault } from "@tuleap/fault";

const HIDDEN_CLASS = "webauthn-hidden";

document.addEventListener("DOMContentLoaded", () => {
    const button = selectOrThrow(document, "#webauthn-button", HTMLButtonElement);
    const input = selectOrThrow(document, "#webauthn-username", HTMLInputElement);
    const form = selectOrThrow(document, "#webauthn-form", HTMLFormElement);
    const icon = selectOrThrow(document, "#webauthn-icon");
    const error = selectOrThrow(document, "#webauthn-error");

    if (!browserSupportsWebAuthn()) {
        // Should not happen as minimum Tuleap support of browsers is above minimum WebAuthn support
        error.innerText = "Your browser does not support WebAuthn";
        error.classList.remove(HIDDEN_CLASS);
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        const username = input.value;
        if (username.length === 0) {
            return;
        }

        icon.classList.remove(HIDDEN_CLASS);
        button.disabled = true;

        postJSON<PublicKeyCredentialRequestOptionsJSON>(uri`/webauthn/authentication-challenge`, {
            username: username,
        })
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
            .match(
                (result) => {
                    const result_input = document.createElement("input");
                    result_input.type = "hidden";
                    result_input.name = "webauthn_result";
                    result_input.value = JSON.stringify(result);
                    form.appendChild(result_input);

                    form.submit();
                },
                (fault) => {
                    icon.classList.add(HIDDEN_CLASS);
                    button.disabled = false;
                    error.innerText = fault.toString();
                    error.classList.remove(HIDDEN_CLASS);
                }
            );
    });

    input.addEventListener("input", () => {
        button.disabled = input.value.length === 0;
    });
});
