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

import { initGettextSync } from "@tuleap/gettext";
import { sendNotifications } from "./send-notifications";

import * as spinner from "./spinner-activation";
import * as feedback from "./feedback-display";
import * as error from "./handle-error";
import * as tlp from "@tuleap/tlp-fetch";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

jest.mock("./spinner-activation");
jest.mock("./feedback-display");
jest.mock("./handle-error");

describe("send-notifications", () => {
    describe("sendNotifactions", () => {
        let form: HTMLFormElement;
        let email_field: HTMLInputElement;
        let message_field: HTMLTextAreaElement;
        let button_field: HTMLButtonElement;

        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();

            email_field = doc.createElement("input");
            email_field.type = "email";
            email_field.name = "invite_buddies_email";

            message_field = doc.createElement("textarea");
            message_field.name = "invite_buddies_message";

            button_field = doc.createElement("button");
            button_field.type = "submit";

            form = doc.createElement("form");
            form.appendChild(email_field);
            form.appendChild(message_field);
            form.appendChild(button_field);

            doc.body.appendChild(form);

            jest.clearAllMocks();
        });

        it("Creates invitation and displays success feedback", async () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            email_field.value = "peter@example.com, wendy@example.com";
            message_field.value = "Lorem ipsum doloret";

            const activateSpinner = jest.spyOn(spinner, "activateSpinner");
            const deactivateSpinner = jest.spyOn(spinner, "deactivateSpinner");
            const displaySuccess = jest.spyOn(feedback, "displaySuccess");
            const handleError = jest.spyOn(error, "handleError");

            const tlpPostMock = jest.spyOn(tlp, "post");
            const response_body = { failures: [] };
            mockFetchSuccess(tlpPostMock, { return_json: response_body });

            await sendNotifications(form, gettext_provider);
            expect(tlpPostMock).toHaveBeenCalledWith(`/api/v1/invitations`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    emails: ["peter@example.com", "wendy@example.com"],
                    custom_message: "Lorem ipsum doloret",
                }),
            });

            expect(activateSpinner).toHaveBeenCalled();
            expect(displaySuccess).toHaveBeenCalledWith(
                ["peter@example.com", "wendy@example.com"],
                response_body,
                gettext_provider,
            );
            expect(handleError).not.toHaveBeenCalled();
            expect(deactivateSpinner).toHaveBeenCalled();
        });

        it("Creates invitation into a project and displays success feedback", async () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            email_field.value = "peter@example.com, wendy@example.com";
            message_field.value = "Lorem ipsum doloret";

            const activateSpinner = jest.spyOn(spinner, "activateSpinner");
            const deactivateSpinner = jest.spyOn(spinner, "deactivateSpinner");
            const displaySuccess = jest.spyOn(feedback, "displaySuccess");
            const handleError = jest.spyOn(error, "handleError");

            const tlpPostMock = jest.spyOn(tlp, "post");
            const response_body = { failures: [] };
            mockFetchSuccess(tlpPostMock, { return_json: response_body });

            const select = document.createElement("select");
            select.name = "invite_buddies_project";
            select.options.add(new Option("", ""));
            select.options.add(new Option("MyProject", "101", true, true));
            select.options.add(new Option("AnotherProject", "102"));
            form.appendChild(select);

            await sendNotifications(form, gettext_provider);
            expect(tlpPostMock).toHaveBeenCalledWith(`/api/v1/invitations`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    emails: ["peter@example.com", "wendy@example.com"],
                    custom_message: "Lorem ipsum doloret",
                    project_id: 101,
                }),
            });

            expect(activateSpinner).toHaveBeenCalled();
            expect(displaySuccess).toHaveBeenCalledWith(
                ["peter@example.com", "wendy@example.com"],
                response_body,
                gettext_provider,
            );
            expect(handleError).not.toHaveBeenCalled();
            expect(deactivateSpinner).toHaveBeenCalled();
        });

        it("Tries to create invitation and displays error", async () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            email_field.value = "peter@example.com, wendy@example.com";
            message_field.value = "Lorem ipsum doloret";

            const activateSpinner = jest.spyOn(spinner, "activateSpinner");
            const deactivateSpinner = jest.spyOn(spinner, "deactivateSpinner");
            const displaySuccess = jest.spyOn(feedback, "displaySuccess");
            const handleError = jest.spyOn(error, "handleError");

            const tlpPostMock = jest.spyOn(tlp, "post");
            mockFetchError(tlpPostMock, {
                error_json: {
                    error: {
                        code: 403,
                        message: "Forbidden",
                    },
                },
            });

            await sendNotifications(form, gettext_provider);
            expect(tlpPostMock).toHaveBeenCalledWith(`/api/v1/invitations`, {
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    emails: ["peter@example.com", "wendy@example.com"],
                    custom_message: "Lorem ipsum doloret",
                }),
            });

            expect(activateSpinner).toHaveBeenCalled();
            expect(displaySuccess).not.toHaveBeenCalled();
            expect(handleError).toHaveBeenCalled();
            expect(deactivateSpinner).toHaveBeenCalled();
        });
    });
});
