/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { SelectedLinkTypeStore } from "./SelectedLinkTypeStore";
import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { FORWARD_DIRECTION } from "../../domain/fields/link-field/LinkType";
import { LinkTypeStub } from "../../../tests/stubs/LinkTypeStub";

describe(`SelectedLinkTypeStore`, () => {
    it(`defaults the type to Untyped`, () => {
        const store = SelectedLinkTypeStore();

        const link_type = store.getSelectedLinkType();
        expect(link_type.shortname).toBe(UNTYPED_LINK);
        expect(link_type.direction).toBe(FORWARD_DIRECTION);
    });

    it(`sets and gets the type of link for new links`, () => {
        const store = SelectedLinkTypeStore();

        const type = LinkTypeStub.buildParentLinkType();
        const new_type = store.setSelectedLinkType(type);

        expect(new_type).toBe(type);
        expect(store.getSelectedLinkType()).toBe(type);
    });
});
