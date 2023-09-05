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

import type { GetText } from "@tuleap/vue2-gettext-init";
import { initListPickersMilestoneSection } from "./init-list-pickers-milestone-section";
import * as listPicker from "@tuleap/list-picker";
import * as disabledPlannableTrackerHelper from "../helper/disabled-plannable-tracker-helper";
import * as disabledIterationTrackerHelper from "../helper/disabled-iteration-tracker-helper";

const createDocument = (): Document => document.implementation.createHTMLDocument();

jest.mock("../helper/disabled-plannable-tracker-helper");
jest.mock("../helper/disabled-iteration-tracker-helper");
describe("initListPickersMilestoneSection", () => {
    const gettext: GetText = {
        gettext: (msgid: string) => {
            return msgid;
        },
    } as GetText;

    it("When program increment tracker selector does not exist, Then nothing is done", () => {
        const create_list_picker = jest.spyOn(listPicker, "createListPicker");
        initListPickersMilestoneSection(createDocument(), gettext);

        expect(create_list_picker).not.toHaveBeenCalled();
    });

    it("When plannable trackers selector does not exist, Then error is thrown", () => {
        const pi_selector = document.createElement("select");
        pi_selector.id = "admin-configuration-program-increment-tracker";

        const doc = createDocument();
        doc.body.appendChild(pi_selector);

        expect(() => initListPickersMilestoneSection(doc, gettext)).toThrowError(
            "admin-configuration-plannable-trackers element does not exist",
        );
    });

    it("When permission prioritize selector does not exist, Then error is thrown", () => {
        const pi_selector = document.createElement("select");
        pi_selector.id = "admin-configuration-program-increment-tracker";

        const plannable_trackers_selector = document.createElement("select");
        plannable_trackers_selector.id = "admin-configuration-plannable-trackers";

        const doc = createDocument();
        doc.body.appendChild(pi_selector);
        doc.body.appendChild(plannable_trackers_selector);

        expect(() => initListPickersMilestoneSection(doc, gettext)).toThrowError(
            "admin-configuration-permission-prioritize element does not exist",
        );
    });

    it("When iteration tracker selector does not exist, Then error is thrown", () => {
        createListPickerSpy();
        const pi_selector = document.createElement("select");
        pi_selector.id = "admin-configuration-program-increment-tracker";

        const plannable_trackers_selector = document.createElement("select");
        plannable_trackers_selector.id = "admin-configuration-plannable-trackers";

        const permissions_selector = document.createElement("select");
        permissions_selector.id = "admin-configuration-permission-prioritize";

        const doc = createDocument();
        doc.body.setAttribute("data-user-locale", "en-EN");
        doc.body.appendChild(pi_selector);
        doc.body.appendChild(plannable_trackers_selector);
        doc.body.appendChild(permissions_selector);
        expect(() => initListPickersMilestoneSection(doc, gettext)).toThrowError(
            "admin-configuration-iteration-tracker element does not exist",
        );
    });

    it("When all sectors exist, Then listpicker is called 4 times", () => {
        const pi_selector = document.createElement("select");
        pi_selector.id = "admin-configuration-program-increment-tracker";

        const plannable_trackers_selector = document.createElement("select");
        plannable_trackers_selector.id = "admin-configuration-plannable-trackers";

        const permissions_selector = document.createElement("select");
        permissions_selector.id = "admin-configuration-permission-prioritize";

        const iteration_selector = document.createElement("select");
        iteration_selector.id = "admin-configuration-iteration-tracker";
        iteration_selector.options.add(new Option("", "", false, false));
        iteration_selector.options.add(new Option("Feature", "895", false, true));

        const doc = createDocument();
        doc.body.setAttribute("data-user-locale", "en-EN");
        doc.body.appendChild(pi_selector);
        doc.body.appendChild(plannable_trackers_selector);
        doc.body.appendChild(permissions_selector);
        doc.body.appendChild(iteration_selector);

        const create_list_picker = createListPickerSpy();
        const disabled_plannable_trackers = jest.spyOn(
            disabledPlannableTrackerHelper,
            "disabledPlannableTrackers",
        );
        const disabled_iteration_tracker = jest.spyOn(
            disabledIterationTrackerHelper,
            "disabledIterationTrackersFromProgramIncrementAndPlannableTrackers",
        );

        initListPickersMilestoneSection(doc, gettext);

        expect(create_list_picker).toHaveBeenNthCalledWith(1, pi_selector, {
            is_filterable: true,
            locale: "en-EN",
            placeholder: "Choose a source tracker for Program Increments",
        });
        expect(create_list_picker).toHaveBeenNthCalledWith(2, plannable_trackers_selector, {
            is_filterable: true,
            locale: "en-EN",
            placeholder: "Choose which trackers can be planned",
        });
        expect(create_list_picker).toHaveBeenNthCalledWith(3, permissions_selector, {
            is_filterable: true,
            locale: "en-EN",
            placeholder: "Choose who can prioritize and plan items",
        });
        expect(create_list_picker).toHaveBeenNthCalledWith(4, iteration_selector, {
            is_filterable: true,
            locale: "en-EN",
            placeholder: "Choose a source tracker for Iterations",
        });
        expect(disabled_plannable_trackers).toHaveBeenCalledWith(doc, pi_selector);
        expect(disabled_iteration_tracker).toHaveBeenCalledWith(doc, "", []);
    });
});

function createListPickerSpy(): jest.SpyInstance<listPicker.ListPicker> {
    return jest.spyOn(listPicker, "createListPicker").mockReturnValue({
        destroy: () => {
            // Nothing to do since we did not really create something
        },
    });
}
