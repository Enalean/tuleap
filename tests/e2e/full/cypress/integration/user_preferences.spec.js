/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

function assertFeedbackContainsMessage(expected_feedback_message) {
    cy.get("[data-test=feedback]").contains(expected_feedback_message);
}

describe("User preferences", () => {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.heisenbergLogin();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    describe("in the [account] Tab", () => {
        beforeEach(() => {
            cy.visit("/account/");
        });

        describe("User is able to", () => {
            it("Change his name", () => {
                cy.get("[data-test=user-real-name]").clear().type("Heisenberg");
                cy.get("[data-test=user-prefs-submit-button]").click();

                cy.get("[data-test=user-real-name]").should("have.value", "Heisenberg");
                assertFeedbackContainsMessage("Real name successfully updated");
            });

            it("Change his email address", () => {
                cy.get("[data-test=user-email]").clear().type("heisenberg@vamonos-pest.us");
                cy.get("[data-test=user-prefs-submit-button]").click();

                cy.get("[data-test=user-prefs-email-need-confirmation-warning]").contains(
                    "An email change was requested, please check your inbox to complete the change."
                );
                assertFeedbackContainsMessage(
                    "New email was successfully saved. To complete the change, please click on the confirmation link you will receive by email (new address)."
                );
            });

            it("Change his timezone", () => {
                cy.get("[data-test=user-timezone]").select("America/Denver", { force: true });
                cy.get("[data-test=user-prefs-submit-button]").click();

                cy.get("[data-test=user-timezone]").should("have.value", "America/Denver");
                assertFeedbackContainsMessage("Timezone successfully updated");
            });

            it("Change his avatar", () => {
                cy.get("[data-test=account-information-avatar-button]").click();
                cy.get("[data-test=account-information-avatar-modal-select-file]")
                    .uploadFixtureFile("heisenberg.jpg", "image/jpg")
                    .trigger("change", { force: true });

                cy.get("[data-test=user-prefs-save-avatar-button]").click();
                assertFeedbackContainsMessage("Avatar changed!");
            });

            it("Change his current avatar for the default one", () => {
                cy.get("[data-test=account-information-avatar-button]").click();
                cy.get("[data-test=account-information-avatar-modal-use-default-button]").click();
                cy.get("[data-test=user-prefs-save-avatar-button]").click();

                assertFeedbackContainsMessage("Avatar changed!");
            });
        });
    });

    describe("in the [Security] Tab", () => {
        function typePasswords(current_password, new_password) {
            cy.get("[data-test=current_password]").type(current_password);
            cy.get("[data-test=new_password]").type(new_password);
            cy.get("[data-test=repeat_new_password]").type(new_password);
        }

        beforeEach(() => {
            cy.visit("/account/security");
        });

        describe("change the password", () => {
            const actual_password = "Correct Horse Battery Staple";
            const temporary_password = "Blue-Meth-99-1%-pure";

            afterEach(() => {
                // Rollback old password, let's not break the tests
                typePasswords(temporary_password, actual_password);
                cy.get("[data-test=user-prefs-update-password]").click();
            });

            it("is successful when it is a safe password", () => {
                typePasswords(actual_password, temporary_password);

                cy.get("[data-test=user-prefs-update-password]").click();

                assertFeedbackContainsMessage("Password successfully updated");
            });
        });

        it("the user can activate the 'remember me' option", () => {
            cy.get("[data-test=account-remember-me]").click({ force: true });

            assertFeedbackContainsMessage("User preferences successfully updated");
            assertFeedbackContainsMessage(
                "You need to logout & login again for this to be taken into account"
            );
        });
    });

    describe("[Notifications] tab", () => {
        beforeEach(() => {
            cy.visit("/account/notifications");
        });

        it("allows user to receive emails about site updates and security notices", () => {
            cy.get("[data-test=user-prefs-site-updates]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();

            cy.get("[data-test=user-prefs-site-updates]").should("have.checked", "checked");

            assertFeedbackContainsMessage("User preferences successfully updated");
        });

        it("allows user to receive community mailings", () => {
            cy.get("[data-test=user-prefs-community-mailing]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();

            cy.get("[data-test=user-prefs-community-mailing]").should("have.checked", "checked");

            assertFeedbackContainsMessage("User preferences successfully updated");
        });

        it("allows user to change the format of the tracker emails to text", () => {
            cy.get("[data-test=user-prefs-text-format]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();
            cy.get("[data-test=user-prefs-text-format]").should("have.checked", "checked");

            assertFeedbackContainsMessage("Email format preference successfully updated");
        });

        it("allows user to change the format of the tracker emails to HTML", () => {
            cy.get("[data-test=user-prefs-html-format]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();
            cy.get("[data-test=user-prefs-html-format]").should("have.checked", "checked");

            assertFeedbackContainsMessage("Email format preference successfully updated");
        });
    });

    describe("in the [Keys & Tokens] tab", () => {
        beforeEach(() => {
            cy.visit("/account/keys-tokens");
        });

        describe("in the SSH keys section", () => {
            it("the user can add his public SSH key", () => {
                cy.get("[data-test=add-ssh-key-button]").click();
                cy.fixture("heisenberg.pub", "utf-8").then((heisenberg_public_ssh_key) => {
                    cy.get("[data-test=ssh-key]").type(heisenberg_public_ssh_key);
                    cy.get("[data-test=submit-new-ssh-key-button]").click();
                    assertFeedbackContainsMessage(
                        "SSH key(s) updated in database, will be propagated on filesystem in a few minutes, please be patient."
                    );

                    cy.get("[data-ssh_key_value]").should("have.length", 1);
                });
            });

            it("the user can remove his public SSH key", () => {
                cy.get("[data-test=user-prefs-remove-ssh-key-checkbox]").click();
                cy.get("[data-test=remove-ssh-keys-button]").click();

                assertFeedbackContainsMessage(
                    "SSH key(s) updated in database, will be propagated on filesystem in a few minutes, please be patient."
                );

                cy.get("[data-ssh_key_value]").should("have.length", 0);
            });
        });

        describe("in the personal access key section", () => {
            it("the user can generate a personal access key", () => {
                cy.get("[data-test=generate-access-key-button]").click();
                cy.get("[data-test=access-key-description]").type("An access key for GIT and REST");
                cy.get("[data-test=user-prefs-personal-access-key-scope-option]").click({
                    multiple: true,
                });
                cy.get("[data-test=access-key-expiration-date-picker]").type("2099-12-31", {
                    force: true,
                });
                cy.get("[data-test=generate-new-access-key-button]").click();

                cy.get("[data-test=user-prefs-add-personal-access-key-feedback]").contains(
                    "Here is your new access key. Please make sure to copy it, you won't be able to see it again!"
                );
                cy.get("[data-test=user-prefs-new-api-key]").should("exist");
                cy.get("[data-test=user-prefs-personal-access-key]").should("have.length", 1);
            });

            it("the user can revoke his personal access key", () => {
                cy.get("[data-test=user-prefs-personal-access-key-checkbox]").click();
                cy.get("[data-test=button-revoke-access-tokens]").click();

                assertFeedbackContainsMessage("Access keys have been successfully deleted.");

                cy.get("[data-test=user-prefs-personal-access-key]").should("have.length", 0);
            });
        });

        describe("in the SVN Tokens section", () => {
            it("the user is able to create a SVN token", () => {
                cy.get("[data-test=generate-svn-token-button]").click();
                cy.get("[data-test=svn-token-description]").type("My handsome SVN token");
                cy.get("[data-test=generate-new-svn-token-button]").click();

                cy.get("[data-test=user-prefs-add-svn-token-feedback]").contains(
                    "Here is your new SVN token. Please make sure you copy it, you won't be able to see it again!"
                );
                cy.get("[data-test=user-prefs-new-svn-token]").should("exist");

                cy.get("[data-test=user-prefs-svn-token]").should("have.length", 1);
            });

            it("the user is able to revoke his SVN tokens", () => {
                cy.get("[data-test=user-prefs-revoke-svn-token-checkbox]").click();
                cy.get("[data-test=button-revoke-svn-tokens]").click();
                cy.get("[data-test=user-prefs-svn-token]").should("have.length", 0);

                assertFeedbackContainsMessage("SVN tokens have been successfully deleted");
            });
        });
    });
});
