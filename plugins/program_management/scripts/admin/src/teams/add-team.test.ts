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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { addTeamInProgram } from "./add-team";
import * as api from "../api/manage-team";
import * as restErrorHelper from "../helper/rest-error-helper";
import * as buttonAddTeamHelper from "../helper/button-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

const createDocument = (): Document => {
    const doc = document.implementation.createHTMLDocument();
    doc.body.insertAdjacentHTML(
        "beforeend",
        `<div id="program-management-add-team-error-rest"></div>`,
    );

    return doc;
};

vi.mock("../api/manage-team", () => {
    return {
        manageTeamOfProgram(): Promise<Response> {
            return Promise.resolve(new Response());
        },
    };
});

vi.useFakeTimers();

describe("AddTeam", () => {
    describe("addTeamInProgram", () => {
        let manage_team: MockInstance,
            reset_rest_error: MockInstance,
            set_rest_error_message: MockInstance,
            set_button_to_disabled: MockInstance,
            reset_button: MockInstance,
            button_to_add_team: HTMLButtonElement;

        beforeEach(() => {
            manage_team = vi.spyOn(api, "manageTeamOfProgram");
            reset_rest_error = vi.spyOn(restErrorHelper, "resetRestErrorAlert");
            set_rest_error_message = vi.spyOn(restErrorHelper, "setRestErrorMessage");
            set_button_to_disabled = vi.spyOn(
                buttonAddTeamHelper,
                "setButtonToDisabledWithSpinner",
            );
            reset_button = vi.spyOn(buttonAddTeamHelper, "resetButtonToAddTeam");

            button_to_add_team = document.createElement("button");
            button_to_add_team.id = "program-management-add-team-button";
            button_to_add_team.insertAdjacentHTML("beforeend", `<i></i>`);

            Object.defineProperty(window, "location", {
                value: {
                    hash: "",
                    reload: vi.fn(),
                },
            });
        });

        it("Given document without button to add team, Then error is thrown", () => {
            expect(() => addTeamInProgram(125, createDocument())).toThrow(
                "Button to add team does not exist",
            );
        });

        it("Given no selected team id, Then api is not called on click", () => {
            const doc = getDocumentWithButton(button_to_add_team, 0);

            addTeamInProgram(125, doc);
            button_to_add_team.click();

            expect(manage_team).not.toHaveBeenCalled();
        });

        it("Given a valid selected team id, Then api is called on click", async () => {
            const doc = getDocumentWithButton(button_to_add_team, 2);

            addTeamInProgram(125, doc);
            button_to_add_team.click();

            await vi.runOnlyPendingTimersAsync();
            expect(manage_team).toHaveBeenCalledWith({ program_id: 125, team_ids: [140] });
            expect(reset_rest_error).toHaveBeenCalled();
            expect(set_button_to_disabled).toHaveBeenCalled();
            expect(reset_button).toHaveBeenCalled();
        });

        it("Given rest error from API, Then error is displayed", async () => {
            const doc = getDocumentWithButton(button_to_add_team, 2);

            manage_team.mockImplementation(() =>
                Promise.reject(
                    new FetchWrapperError("Not found", {
                        json: (): Promise<{ error: { code: number; message: string } }> =>
                            Promise.resolve({ error: { code: 400, message: "Team not found" } }),
                    } as Response),
                ),
            );

            addTeamInProgram(125, doc);
            button_to_add_team.click();

            await vi.runOnlyPendingTimersAsync();
            expect(manage_team).toHaveBeenCalledWith({ program_id: 125, team_ids: [140] });
            expect(reset_rest_error).toHaveBeenCalled();
            expect(set_button_to_disabled).toHaveBeenCalled();
            expect(set_rest_error_message).toHaveBeenCalledWith(
                doc,
                "program-management-add-team-error-rest",
                "400 Team not found",
            );
            expect(reset_button).toHaveBeenCalled();
        });

        it("Given rest error with i18n message, Then it's displayed", async () => {
            const doc = getDocumentWithButton(button_to_add_team, 2);

            manage_team.mockImplementation(() =>
                Promise.reject(
                    new FetchWrapperError("Not found", {
                        json(): Promise<{ error: { code: number; message: string } }> {
                            return Promise.resolve({
                                error: {
                                    code: 400,
                                    message: "Team not found",
                                    i18n_error_message: "L'Équipe n'est pas trouvée",
                                },
                            });
                        },
                    } as Response),
                ),
            );

            addTeamInProgram(125, doc);
            button_to_add_team.click();

            await vi.runOnlyPendingTimersAsync();
            expect(manage_team).toHaveBeenCalledWith({ program_id: 125, team_ids: [140] });
            expect(reset_rest_error).toHaveBeenCalled();
            expect(set_button_to_disabled).toHaveBeenCalled();
            expect(set_rest_error_message).toHaveBeenCalledWith(
                doc,
                "program-management-add-team-error-rest",
                "400 L'Équipe n'est pas trouvée",
            );
            expect(reset_button).toHaveBeenCalled();
        });
    });
});

function getDocumentWithButton(button: HTMLButtonElement, selected_index: number): Document {
    const doc = createDocument();
    doc.body.appendChild(button);

    const select = document.createElement("select");
    select.id = "program-management-choose-teams";
    doc.body.appendChild(select);
    select.options.add(new Option("", ""));
    select.options.add(new Option("first team", "666"));
    select.options.add(new Option("second team", "140"));
    select.selectedIndex = selected_index;

    return doc;
}
