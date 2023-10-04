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

import { WEB_UI_SESSION } from "@tuleap/cypress-utilities-support";

describe("User preferences", () => {
    let username: string, password: string;

    before(() => {
        const now = Date.now();
        username = "test_prefs_" + now;
        password = "pwd_" + now;

        cy.updatePlatformAndMakeUserInAdminApprovalMode();

        cy.anonymousSession();
        cy.visit("/account/register.php");
        cy.get("[data-test=user-login]").type(username);
        cy.get("[data-test=user-email]").type(`${username}@localhost`);
        cy.get("[data-test=user-pw]").type(password);
        cy.get("[data-test=user-pw2]").type(password);
        cy.get("[data-test=user-prefs-site-updates]").click();
        cy.get("[data-test=user-name]").type(`Test Prefs ${now}`);
        cy.get("[data-test=form_register_purpose]").type(`Create me`);
        cy.get("[data-test=register-user-button]").click();

        cy.siteAdministratorSession();
        cy.visit("/admin/approve_pending_users.php?page=pending");
        cy.contains(".siteadmin-pending-user", username)
            .find("[name=action_select][value=activate]")
            .click();
    });

    function newUserSession(): void {
        cy.session([WEB_UI_SESSION, username], () => {
            cy.visit("/");
            cy.get("[data-test=form_loginname]").type(username);
            cy.get("[data-test=form_pw]").type(`${password}{enter}`);
        });
    }

    describe("in the [account] Tab", () => {
        describe("User is able to", () => {
            it("Change his data", () => {
                newUserSession();
                cy.visit("/account/");
                cy.get("[data-test=user-real-name]").clear().type("Heisenberg");
                cy.get("[data-test=user-email]").clear().type("heisenberg@vamonos-pest.us");
                cy.get("[data-test=user-timezone]").select("America/Denver", { force: true });
                cy.get("[data-test=user-prefs-submit-button]").click();

                assertFeedbackContainsMessage(
                    "New email was successfully saved. To complete the change, please click on the confirmation link you will receive by email (new address).",
                );
                cy.visit("/account/");

                cy.get("[data-test=user-real-name]").should("have.value", "Heisenberg");
                cy.get("[data-test=user-prefs-email-need-confirmation-warning]").contains(
                    "An email change was requested, please check your inbox to complete the change.",
                );

                cy.get("[data-test=user-timezone]").should("have.value", "America/Denver");
            });

            it("Change his avatar", () => {
                newUserSession();
                cy.visit("/account/");
                cy.get("[data-test=account-information-avatar-button]").click();
                cy.get("[data-test=account-information-avatar-modal-select-file]").selectFile(
                    "cypress/fixtures/heisenberg.jpg",
                );

                cy.get("[data-test=user-prefs-save-avatar-button]").click();
                assertFeedbackContainsMessage("Avatar changed!");
            });

            it("Change his current avatar for the default one", () => {
                newUserSession();
                cy.visit("/account/");
                cy.get("[data-test=account-information-avatar-button]").click();
                cy.get("[data-test=account-information-avatar-modal-use-default-button]").click();
                cy.get("[data-test=user-prefs-save-avatar-button]").click();

                assertFeedbackContainsMessage("Avatar changed!");
            });
        });
    });

    describe("in the [Security] Tab", () => {
        function typePasswords(current_password: string, new_password: string): void {
            cy.get("[data-test=current_password]").type(current_password);
            cy.get("[data-test=new_password]").type(new_password);
            cy.get("[data-test=repeat_new_password]").type(new_password);
        }

        describe("change the password", () => {
            it("is successful when it is a safe password", () => {
                const temporary_password = "Blue-Meth-99-1%-pure";

                newUserSession();
                cy.visit("/account/security");
                typePasswords(password, temporary_password);

                cy.get("[data-test=user-prefs-update-password]").click();

                assertFeedbackContainsMessage("Password successfully updated");

                cy.log("Rollback to the old password to avoid breaking the other tests");
                typePasswords(temporary_password, password);
                cy.get("[data-test=user-prefs-update-password]").click();
            });
        });

        it("the user can activate the 'remember me' option", () => {
            newUserSession();
            cy.visit("/account/security");
            cy.get("[data-test=account-remember-me]").click({ force: true });

            assertFeedbackContainsMessage("User preferences successfully updated");
            assertFeedbackContainsMessage(
                "You need to logout & login again for this to be taken into account",
            );
        });
    });

    describe("[Notifications] tab", () => {
        it("allows user to receive emails about site updates and security notices", () => {
            newUserSession();
            cy.visit("/account/notifications");
            cy.get("[data-test=user-prefs-site-updates]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();

            cy.get("[data-test=user-prefs-site-updates]").should("have.checked", "checked");

            assertFeedbackContainsMessage("User preferences successfully updated");
        });

        it("allows user to receive community mailings", () => {
            newUserSession();
            cy.visit("/account/notifications");
            cy.get("[data-test=user-prefs-community-mailing]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();

            cy.get("[data-test=user-prefs-community-mailing]").should("have.checked", "checked");

            assertFeedbackContainsMessage("User preferences successfully updated");
        });

        it("allows user to change the format of the tracker emails to text", () => {
            newUserSession();
            cy.visit("/account/notifications");
            cy.get("[data-test=user-prefs-text-format]").click();
            cy.get("[data-test=user-prefs-update-notification]").click();

            cy.visit("/account/notifications");
            cy.get("[data-test=user-prefs-text-format]").should("have.checked", "checked");
        });
    });

    describe("in the [Keys & Tokens] tab", () => {
        describe("in the SSH keys section", () => {
            it("the user can manipulate his public SSH key", () => {
                newUserSession();
                cy.visit("/account/keys-tokens");
                cy.get("[data-test=add-ssh-key-button]").click();
                cy.get("[data-test=ssh-key]")
                    .type(".")
                    .then(($input) => {
                        $input.val(
                            "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFCu3WYbOeBkXkDaKiV3AX6noIw16pjjrftXyiRjvP9O heisenberg@example",
                        );
                    });

                cy.get("[data-test=submit-new-ssh-key-button]").click();
                assertFeedbackContainsMessage(
                    "SSH key(s) updated in database, will be propagated on filesystem in a few minutes, please be patient.",
                );

                cy.get("[data-ssh_key_value]").should("have.length", 1);

                cy.log("revoke the SSH key");
                cy.get("[data-test=user-prefs-remove-ssh-key-checkbox]").click();
                cy.get("[data-test=remove-ssh-keys-button]").click();

                assertFeedbackContainsMessage(
                    "SSH key(s) updated in database, will be propagated on filesystem in a few minutes, please be patient.",
                );

                cy.get("[data-ssh_key_value]").should("have.length", 0);
            });
        });

        describe("in the personal access key section", () => {
            it("the user can manipulate his personal access key", () => {
                newUserSession();
                cy.visit("/account/keys-tokens");
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
                    "Here is your new access key. Please make sure to copy it, you won't be able to see it again!",
                );
                cy.get("[data-test=user-prefs-new-api-key]").should("exist");
                cy.get("[data-test=user-prefs-personal-access-key]").should("have.length", 1);

                cy.log("revoke the access key");
                cy.get("[data-test=user-prefs-personal-access-key-checkbox]").click();
                cy.get("[data-test=button-revoke-access-tokens]").click();

                assertFeedbackContainsMessage("Access keys have been successfully deleted.");

                cy.get("[data-test=user-prefs-personal-access-key]").should("have.length", 0);
            });
        });
    });

    describe("in the [Appearance & Language] tab", () => {
        describe("in the language section", () => {
            it("the user can choose his his language", () => {
                newUserSession();
                cy.visit("/account/appearance");
                cy.get("[data-test=user-prefs-language-selector-fr_FR]").click();
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();

                assertFeedbackContainsMessage("User preferences successfully updated");

                cy.get("[data-test=user-prefs-language-selector-fr_FR]").should("be.checked");
                cy.get("[data-test=user-preferences-title]").contains("Préférences");

                cy.log("rollback to English");
                cy.get("[data-test=user-prefs-language-selector-en_US]").click();
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();

                assertFeedbackContainsMessage("Les préférences utilisateur ont été mises à jour");

                cy.get("[data-test=user-prefs-language-selector-en_US]").should("be.checked");
                cy.get("[data-test=user-preferences-title]").contains("Preferences");
            });
        });

        describe("in the Theme color section", () => {
            function assertColorPreviewIs(color_name: string): void {
                cy.get("[data-test=user-preferences-section-appearance-preview]").should(
                    "have.class",
                    `user-preferences-section-appearance-preview-${color_name}`,
                );
            }

            it("the user can change the theme color of Tuleap", () => {
                newUserSession();
                cy.visit("/account/appearance");
                cy.get("[data-test=user-preferences-color-selector]").select("blue", {
                    force: true,
                });
                assertColorPreviewIs("blue");
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();

                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").should("have.class", `theme-blue`);
                cy.get("[data-test=user-preferences-color-selector]").should("have.value", "blue");
            });
        });

        describe("the user can set the display density", () => {
            it("to the condensed/comfortable mode", () => {
                newUserSession();
                cy.visit("/account/appearance");
                cy.get("[data-test=user-prefs-display-density-condensed]").click();
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();
                assertFeedbackContainsMessage("User preferences successfully updated");

                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").should("have.class", "theme-condensed");

                cy.log("rollback to comfortable mode");
                cy.get("[data-test=user-prefs-display-density-comfortable]").click();
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();
                assertFeedbackContainsMessage("User preferences successfully updated");

                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").should("not.have.class", "theme-condensed");
            });
        });

        describe("in the Enable accessibility mode", () => {
            it("the user can enable the option", () => {
                newUserSession();
                cy.visit("/account/appearance");
                cy.get("[data-test=user-preferences-accessibility-selector]").click();
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();
                cy.visit("/account/appearance");

                cy.get("[data-test=user-preferences-accessibility-selector]").should("be.checked");
                cy.get("[data-user-has-accessibility-mode]")
                    .invoke("attr", "data-user-has-accessibility-mode")
                    .should("eq", "1");
            });
        });

        describe("in the username display section", () => {
            it("the user can choose the way usernames are displayed", () => {
                newUserSession();
                cy.visit("/account/appearance");
                cy.get("[data-test=user-prefs-username-display-format-select]").select("2");
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();
                cy.visit("/account/appearance");

                cy.get("[data-test=user-prefs-username-display-format-select]").should(
                    "have.value",
                    "2",
                );
            });
        });

        describe("in the relative dates display section", () => {
            it("the user can choose the way relative dates are displayed", () => {
                newUserSession();
                cy.visit("/account/appearance");
                cy.get("[data-test=user-prefs-relative-dates-display-format-select]").select(
                    "absolute_first-relative_shown",
                );
                cy.get("[data-test=user-prefs-appearance-section-submit]").click();
                cy.visit("/account/appearance");

                cy.get("[data-test=user-prefs-relative-dates-display-format-select]").should(
                    "have.value",
                    "absolute_first-relative_shown",
                );
            });
        });
    });

    describe("in the [Edition & CSV] tab", () => {
        describe("in the default tracker text fields format section", () => {
            it("the user can choose the HTML format", () => {
                newUserSession();
                cy.visit("/account/edition");
                cy.get("[data-test=user-prefs-tracker-default-format-html]").click();
                cy.get("[data-test=user-prefs-edition-tab-submit-button]").click();

                cy.visit("/account/edition");

                cy.get("[data-test=user-prefs-tracker-default-format-html]").should("be.checked");
            });
        });

        describe("in the CSV separator section", () => {
            it("the user can choose separators to semicolon", () => {
                newUserSession();
                cy.visit("/account/edition");
                cy.get("[data-test=user-prefs-csv-separator-semicolon]").click();
                cy.get("[data-test=user-prefs-edition-tab-submit-button]").click();

                cy.visit("/account/edition");

                cy.get("[data-test=user-prefs-csv-separator-semicolon]").should("be.checked");
            });
        });

        describe("in the CSV date format section", () => {
            it("the user can choose the  date format to day/month/year", () => {
                newUserSession();
                cy.visit("/account/edition");
                cy.get("[data-test=user-prefs-csv-dateformat-day-month-year]").click();
                cy.get("[data-test=user-prefs-edition-tab-submit-button]").click();

                cy.visit("/account/edition");

                cy.get("[data-test=user-prefs-csv-dateformat-day-month-year]").should("be.checked");
            });
        });
    });
});

function assertFeedbackContainsMessage(expected_feedback_message: string): void {
    cy.get("[data-test=feedback]").contains(expected_feedback_message);
}
