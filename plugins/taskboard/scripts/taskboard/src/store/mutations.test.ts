/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { State } from "../type";
import * as mutations from "./mutations";

describe("addSwimlanes", () => {
    it("add swimlanes to existing ones", () => {
        const state: State = {
            swimlanes: [
                {
                    card: {
                        id: 42,
                        label: "Story 1",
                        xref: "story #42",
                        rank: 10
                    }
                }
            ]
        } as State;
        mutations.addSwimlanes(state, [
            {
                card: {
                    id: 43,
                    label: "Story 2",
                    xref: "story #43",
                    rank: 11
                }
            },
            {
                card: {
                    id: 44,
                    label: "Story 3",
                    xref: "story #44",
                    rank: 12
                }
            }
        ]);
        expect(state.swimlanes).toStrictEqual([
            {
                card: {
                    id: 42,
                    label: "Story 1",
                    xref: "story #42",
                    rank: 10
                }
            },
            {
                card: {
                    id: 43,
                    label: "Story 2",
                    xref: "story #43",
                    rank: 11
                }
            },
            {
                card: {
                    id: 44,
                    label: "Story 3",
                    xref: "story #44",
                    rank: 12
                }
            }
        ]);
    });
});
