/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import { augment, BacklogItemRepresentation } from "./backlog-item-factory";

describe("BacklogItemFactory", () => {
    describe("augment", () => {
        const item = {
            artifact: { tracker: { id: 78 } },
            accept: { trackers: [{ id: 123 }, { id: 895 }] },
            status: "Open",
        } as BacklogItemRepresentation;

        const backlog_item = augment(item);

        it("adds allowed tracker types to backlog item", () => {
            expect(backlog_item.accepted_types.toString()).toEqual("trackerId123|trackerId895");
            expect(backlog_item.trackerId).toEqual("trackerId78");
        });

        it("adds children properties", () => {
            const expected = {
                data: [],
                loaded: false,
                collapsed: true,
            };

            expect(backlog_item.children).toEqual(expected);
        });

        it("has method isOpen", () => {
            expect(backlog_item.isOpen()).toEqual(true);
        });
    });
});
