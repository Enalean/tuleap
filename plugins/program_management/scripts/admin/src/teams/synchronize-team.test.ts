/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

import { DocumentAdapter } from "../dom/DocumentAdapter";
import { initSynchronizeTeamButtons, triggerTeamSynchronization } from "./synchronize-team";
import * as api from "../api/synchronize-team";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("synchronize-team", () => {
    let location: Location;

    beforeEach(() => {
        location = { reload: jest.fn() } as unknown as Location;
    });

    describe("synchronizeTeam", () => {
        it("Do does not launch synchronization", function () {
            const doc = createDocument();

            expect(() =>
                initSynchronizeTeamButtons(new DocumentAdapter(doc), location),
            ).not.toThrow();
        });

        it("should trigger the team synchronization and reload the page", async () => {
            const doc = createDocument();
            const button = document.createElement("button");
            button.dataset.projectLabel = "my-program";
            button.dataset.teamId = "104";
            button.className = "program-management-admin-action-synchronize-button";
            doc.body.appendChild(button);

            const synchronize_team = jest
                .spyOn(api, "synchronizeTeamOfProgram")
                .mockResolvedValue(Promise.resolve({} as Response));

            await triggerTeamSynchronization(button, location);

            expect(synchronize_team).toHaveBeenCalledWith("my-program", 104);
            expect(location.reload).toHaveBeenCalled();
        });
    });
});
