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

import * as getters from "./getters";
import { State } from "./type";
import { Project, UserHistoryEntry } from "../type";

describe("SwitchTo getters", () => {
    describe("filtered_projects", () => {
        it("Filters projects", () => {
            const state: State = {
                projects: [
                    { project_name: "Acme" } as Project,
                    { project_name: "ACME Corp" } as Project,
                    { project_name: "Another project" } as Project,
                ],
                filter_value: "acme",
            } as State;

            expect(getters.filtered_projects(state)).toStrictEqual([
                { project_name: "Acme" } as Project,
                { project_name: "ACME Corp" } as Project,
            ]);
        });
    });

    describe("filtered_history", () => {
        it("Filters recent items", () => {
            const state: State = {
                history: {
                    entries: [
                        { title: "Acme" } as UserHistoryEntry,
                        { title: "ACME Corp" } as UserHistoryEntry,
                        { title: "Another entry" } as UserHistoryEntry,
                        { xref: "wiki #ACME" } as UserHistoryEntry,
                    ],
                },
                filter_value: "acme",
            } as State;

            expect(getters.filtered_history(state)).toStrictEqual({
                entries: [
                    { title: "Acme" } as UserHistoryEntry,
                    { title: "ACME Corp" } as UserHistoryEntry,
                    { xref: "wiki #ACME" } as UserHistoryEntry,
                ],
            });
        });
    });
});
