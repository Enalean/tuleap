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

describe("User preferences | WebAuthn", () => {
    beforeEach(() => {
        cy.projectMemberSession();
        cy.visit("/plugins/webauthn/account");
    });

    it("can register a new passkey", () => {
        cy.createAuthenticator().then(() => {
            cy.get("[data-test=no-passkey]").should("be.visible");

            cy.get("[data-test=add-button]").should("be.visible");
            cy.get("[data-test=add-button]").click();

            cy.get("[data-test=name-modal-input]").type("My awesome key");
            cy.get("[data-test=name-modal-button]").click();

            cy.visit("/account/keys-tokens");
            cy.get("[data-test=generate-access-key-button]").click();
            cy.get("[data-test=webauthn-modal-submit-button]:visible").click();
            cy.get("[data-test=user-prefs-personal-access-key-scope-option]").click({
                multiple: true,
            });
            cy.get("[data-test=generate-new-access-key-button]").click();

            cy.get("[data-test=user-prefs-personal-access-key]").should("have.length", 1);

            cy.log("revoke the access key");
            cy.get("[data-test=user-prefs-personal-access-key-checkbox]").click();
            cy.get("[data-test=button-revoke-access-tokens]").click();
            cy.get("[data-test=user-prefs-personal-access-key]").should("have.length", 0);

            cy.visit("/plugins/webauthn/account");
            cy.get("[data-test=remove-button]").click();
            cy.get("[data-test=remove-modal-button]").click();

            cy.get("[data-test=no-passkey]").should("be.visible");
        });
    });
});
