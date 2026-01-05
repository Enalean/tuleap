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

import { describe, expect, it, vi } from "vitest";
import { handleTeamRemove } from "./button-to-remove-team-handler";
import * as api from "../api/manage-team";

describe("ButtonToRemoveTeamHandler", () => {
    describe("handleTeamRemove", () => {
        it("Given button without team id, Then error is thrown", () => {
            const button = document.createElement("button");
            expect(() => handleTeamRemove(button, [12], 101)).toThrow("No team id on button");
        });

        it("Given clicking on button, Then API is queried without id of team to remove", () => {
            const button = document.createElement("button");
            button.setAttribute("data-team-id", "666");

            const manage_team = vi
                .spyOn(api, "manageTeamOfProgram")
                .mockResolvedValue(new Response());

            Object.defineProperty(window, "location", {
                value: {
                    hash: "",
                    reload: vi.fn(),
                },
            });

            handleTeamRemove(button, [12, 666], 101);

            button.click();

            expect(manage_team).toHaveBeenCalledWith({ program_id: 101, team_ids: [12] });
        });
    });
});
