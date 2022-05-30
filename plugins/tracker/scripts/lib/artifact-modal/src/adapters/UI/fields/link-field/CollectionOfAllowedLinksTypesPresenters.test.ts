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
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";

describe("CollectionOfAllowedLinksTypesPresenters", () => {
    it(`Given a collection of allowed links types,
        then it should build a collection of presenters for each type and each direction`, () => {
        const allowed_types = [
            {
                shortname: IS_CHILD_LINK_TYPE,
                forward_label: "Child",
                reverse_label: "Parent",
            },
            {
                shortname: "_covered_by",
                forward_label: "Covered by",
                reverse_label: "Covers",
            },
        ];

        const presenter = CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
            VerifyHasParentLinkStub.withNoParentLink(),
            allowed_types
        );

        expect(presenter.is_parent_type_disabled).toBe(false);
        expect(presenter.types).toStrictEqual([
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

    it(`should mark reverse _is_child type (Parent) as disabled when there is already a reverse _is_child link
        as an Artifact should only have one Parent`, () => {
        const presenter = CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
            VerifyHasParentLinkStub.withParentLink(),
            [
                {
                    shortname: IS_CHILD_LINK_TYPE,
                    forward_label: "Child",
                    reverse_label: "Parent",
                },
            ]
        );
        expect(presenter.is_parent_type_disabled).toBe(true);
        expect(presenter.types).toHaveLength(1);
    });

    it(`Should build an empty presenter`, () => {
        const presenter = CollectionOfAllowedLinksTypesPresenters.buildEmpty();
        expect(presenter.is_parent_type_disabled).toBe(false);
        expect(presenter.types).toHaveLength(0);
    });
});
