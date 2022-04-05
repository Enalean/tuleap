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

import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";

describe("AllowedLinksTypesPresenter", () => {
    it("Given a collection of allowed links types, then it should build a collection of presenters for each type and each direction", () => {
        const allowed_types = [
            {
                shortname: "_is_child",
                forward_label: "Child",
                reverse_label: "Parent",
            },
            {
                shortname: "_covered_by",
                forward_label: "Covered by",
                reverse_label: "Covers",
            },
        ];

        const presenters =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(allowed_types);

        expect(presenters).toEqual([
            {
                forward_type_presenter: {
                    label: "Child",
                    shortname: "_is_child",
                    direction: "forward",
                },
                reverse_type_presenter: {
                    label: "Parent",
                    shortname: "_is_child",
                    direction: "reverse",
                },
            },
            {
                forward_type_presenter: {
                    label: "Covered by",
                    shortname: "_covered_by",
                    direction: "forward",
                },
                reverse_type_presenter: {
                    label: "Covers",
                    shortname: "_covered_by",
                    direction: "reverse",
                },
            },
        ]);
    });
});
