/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { AllowedLinksTypesCollection } from "./AllowedLinksTypesCollection";

describe("AllowedLinksTypesCollection", () => {
    it("should build from a collection of allowed types representations and only keep the _is_child type", () => {
        const types = [
            {
                shortname: "fixed_in",
                forward_label: "Fixed in",
                reverse_label: "Fixes",
            },
            {
                shortname: "_is_child",
                forward_label: "Child",
                reverse_label: "Parent",
            },
        ];

        const collection = AllowedLinksTypesCollection.buildFromTypesRepresentations(types);

        expect(collection.getAll()).toStrictEqual([
            [
                {
                    shortname: "_is_child",
                    direction: "forward",
                    label: "Child",
                },
                {
                    shortname: "_is_child",
                    direction: "reverse",
                    label: "Parent",
                },
            ],
        ]);
    });

    it("should return the reverse child type", () => {
        const collection = AllowedLinksTypesCollection.buildFromTypesRepresentations([
            {
                shortname: "_is_child",
                forward_label: "Child",
                reverse_label: "Parent",
            },
        ]);

        expect(collection.getReverseChildType()).toStrictEqual({
            shortname: "_is_child",
            direction: "reverse",
            label: "Parent",
        });
    });
});
