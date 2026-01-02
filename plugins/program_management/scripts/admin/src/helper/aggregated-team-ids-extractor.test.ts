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

import { describe, expect, it } from "vitest";
import { extractAggregatedTeamIds } from "./aggregated-team-ids-extractor";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("AggregatedTeamIdsExtractor", () => {
    describe("extractAggregatedTeamIds", () => {
        it("Given document with button has not team id data, Then error is thrown", () => {
            const doc = createDocument();

            const button = document.createElement("button");
            button.classList.add("program-management-admin-remove-teams-open-modal-button");

            doc.body.appendChild(button);

            expect(() => extractAggregatedTeamIds(doc)).toThrow("No team id on button");
        });

        it("Given document with button has team id data, Then team ids are returned", () => {
            const doc = createDocument();

            const button_1 = document.createElement("button");
            button_1.classList.add("program-management-admin-remove-teams-open-modal-button");
            button_1.setAttribute("data-team-id", "1");

            const button_2 = document.createElement("button");
            button_2.classList.add("program-management-admin-remove-teams-open-modal-button");
            button_2.setAttribute("data-team-id", "2");

            doc.body.appendChild(button_1);
            doc.body.appendChild(button_2);

            const team_ids = extractAggregatedTeamIds(doc);
            expect(team_ids).toEqual([1, 2]);
        });
    });
});
