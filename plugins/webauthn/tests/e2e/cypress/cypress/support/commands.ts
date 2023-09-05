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

declare global {
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            createAuthenticator(): Promise<string>;
        }
    }
}

Cypress.Commands.add("createAuthenticator", () =>
    Cypress.automation("remote:debugger:protocol", {
        command: "WebAuthn.enable",
        params: {},
    })
        .then(() =>
            Cypress.automation("remote:debugger:protocol", {
                command: "WebAuthn.addVirtualAuthenticator",
                params: {
                    options: {
                        protocol: "ctap2",
                        transport: "internal",
                        hasResidentKey: true,
                        hasUserVerification: true,
                        isUserVerified: true,
                    },
                },
            }),
        )
        .then((result) => result.authenticatorId),
);

export {};
