/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { displaySuccess } from "./feedback-display";
import { initGettextSync } from "@tuleap/gettext";

describe("feedback-display", () => {
    describe("displaySuccess", () => {
        beforeEach(() => {
            document.body.innerHTML = `
                <div id="invite-buddies-modal">
                    <div id="invite-buddies-modal-feedback">
                        <div class="tlp-alert-error">Previous error</div>
                    </div>
                    <div id="invite-buddies-modal-body"></div>
                    <div id="invite-buddies-modal-footer"></div>
                </div>
            `;
        });

        afterEach(() => {
            document.body.innerHTML = "";
        });

        it("Clear existing feedbacks, display the success one and inform that emails have been sent", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: [],
                    already_project_members: [],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(2);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(0);
        });

        it("can displaySuccess multiple times in a row", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["peter@example.com"],
                {
                    failures: [],
                    already_project_members: [],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );
            displaySuccess(
                ["wendy@example.com"],
                {
                    failures: [],
                    already_project_members: [],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(0);
        });

        it("Extracts emails that are in error to display a warning", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: ["peter@example.com"],
                    already_project_members: [],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(0);
        });

        it("should display users that are already project members", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: [],
                    already_project_members: [
                        { email: "peter@example.com", display_name: "Peter Pan (pan)" },
                    ],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(0);
        });

        it("should display known users that are have been added to project members", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: [],
                    already_project_members: [],
                    known_users_added_to_project_members: [
                        { email: "peter@example.com", display_name: "Peter Pan (pan)" },
                    ],
                    known_users_not_alive: [],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(0);
        });

        it("should display known users that are not alive", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: [],
                    already_project_members: [],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [
                        { email: "peter@example.com", display_name: "Peter Pan (pan)" },
                    ],
                    known_users_are_restricted: [],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=not-alive]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(0);
        });

        it("should display known users that are restricted", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: [],
                    already_project_members: [],
                    known_users_added_to_project_members: [],
                    known_users_not_alive: [],
                    known_users_are_restricted: [
                        { email: "peter@example.com", display_name: "Peter Pan (pan)" },
                    ],
                },
                gettext_provider,
            );

            expect(document.body.querySelectorAll("[data-test=success]")).toHaveLength(1);
            expect(document.body.querySelectorAll("[data-test=already-member]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=could-not-be-sent]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=known-user-added]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=not-alive]")).toHaveLength(0);
            expect(document.body.querySelectorAll("[data-test=restricted]")).toHaveLength(1);
        });
    });
});
