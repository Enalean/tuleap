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
import { synchronizeTeam } from "./synchronize-team";
import * as api from "../api/synchronize-team";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("synchronize-team", () => {
    describe("synchronizeTeam", () => {
        it("Do does not launch synchronization", function () {
            const doc = createDocument();

            expect(() => synchronizeTeam(new DocumentAdapter(doc))).not.toThrow();
        });

        it("Synchronize team", function () {
            const doc = createDocument();
            const button = document.createElement("button");
            button.dataset.projectLabel = "my-program";
            button.dataset.teamId = "104";
            button.className = "program-management-admin-action-synchronize-button";
            doc.body.appendChild(button);

            const synchronize_team = jest.spyOn(api, "synchronizeTeamOfProgram");

            expect(() => synchronizeTeam(new DocumentAdapter(doc))).not.toThrow();

            button.click();

            expect(synchronize_team).toHaveBeenCalledWith("my-program", 104);
        });
    });
});
