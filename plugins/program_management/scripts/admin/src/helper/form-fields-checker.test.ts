/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { checkAllFieldAreFilledAndSetErrorMessage } from "./form-fields-checker";
import * as formFieldErrorHelper from "./form-field-error-helper";
import type { GetText } from "@tuleap/gettext";

jest.mock("./form-field-error-helper");
describe("form-fields-checker", function () {
    const gettext: GetText = {
        gettext: (msgid: string) => {
            return msgid;
        },
    } as GetText;
    describe("checkAllFieldAreFilledAndSetErrorMessage", function () {
        let resetErrorOnSelectField: jest.SpyInstance,
            setErrorMessageOnSelectField: jest.SpyInstance;

        beforeEach(() => {
            resetErrorOnSelectField = jest.spyOn(formFieldErrorHelper, "resetErrorOnSelectField");
            setErrorMessageOnSelectField = jest.spyOn(
                formFieldErrorHelper,
                "setErrorMessageOnSelectField",
            );
        });

        afterEach(() => {
            jest.clearAllMocks();
        });

        it("should reset fields and return true when all field are filled", function () {
            expect(
                checkAllFieldAreFilledAndSetErrorMessage(
                    createDocumentWithSelectorWithoutEmptyField(),
                    gettext,
                ),
            ).toBeTruthy();

            expect(resetErrorOnSelectField).toHaveBeenCalledTimes(3);
            expect(setErrorMessageOnSelectField).not.toHaveBeenCalled();
        });

        it("should reset fields and return false when all field are not field", function () {
            expect(
                checkAllFieldAreFilledAndSetErrorMessage(
                    createDocumentWithSelectorWithEmptyField(),
                    gettext,
                ),
            ).toBeFalsy();

            expect(resetErrorOnSelectField).toHaveBeenCalledTimes(3);
            expect(setErrorMessageOnSelectField).toHaveBeenCalledTimes(3);
        });
    });
});

function createDocumentWithSelectorWithEmptyField(): Document {
    const doc = document.implementation.createHTMLDocument();

    const select_pi = document.createElement("select");
    select_pi.id = "admin-configuration-program-increment-tracker";

    const select_plannable_trackers = document.createElement("select");
    select_plannable_trackers.id = "admin-configuration-plannable-trackers";

    const select_permissions = document.createElement("select");
    select_permissions.id = "admin-configuration-permission-prioritize";

    doc.body.appendChild(select_pi);
    doc.body.appendChild(select_plannable_trackers);
    doc.body.appendChild(select_permissions);

    return doc;
}

function createDocumentWithSelectorWithoutEmptyField(): Document {
    const doc = document.implementation.createHTMLDocument();

    const select_pi = document.createElement("select");
    select_pi.id = "admin-configuration-program-increment-tracker";
    select_pi.add(new Option("PI", "8", false, true));

    const select_plannable_trackers = document.createElement("select");
    select_plannable_trackers.id = "admin-configuration-plannable-trackers";
    select_plannable_trackers.add(new Option("Features", "9", false, true));

    const select_permissions = document.createElement("select");
    select_permissions.id = "admin-configuration-permission-prioritize";
    select_permissions.add(new Option("Member", "100_3", false, true));

    doc.body.appendChild(select_pi);
    doc.body.appendChild(select_plannable_trackers);
    doc.body.appendChild(select_permissions);

    return doc;
}
