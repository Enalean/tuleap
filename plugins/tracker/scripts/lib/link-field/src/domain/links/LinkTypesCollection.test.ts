/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import { LinkType } from "./LinkType";
import { LinkTypesCollection } from "./LinkTypesCollection";
import { LinkTypesPairStub } from "../../../tests/stubs/links/LinkTypesPairStub";

describe(`LinkTypesCollection`, () => {
    it(`returns the LinkTypesPairs it was built with`, () => {
        const type_pairs = [LinkTypesPairStub.buildParent(), LinkTypesPairStub.buildCustom()];
        const collection = LinkTypesCollection(type_pairs);
        expect(collection.getAll()).toBe(type_pairs);
    });

    it(`returns the Parent type`, () => {
        const collection = LinkTypesCollection([
            LinkTypesPairStub.buildParent(),
            LinkTypesPairStub.buildCustom(),
        ]);
        const parent_type = collection.getReverseChildType();
        if (!parent_type) {
            throw Error("Expected the parent type to be defined");
        }
        expect(LinkType.isReverseChild(parent_type)).toBe(true);
    });

    it(`when no Parent type is allowed, it will return undefined`, () => {
        const collection = LinkTypesCollection([LinkTypesPairStub.buildCustom()]);
        expect(collection.getReverseChildType()).toBeUndefined();
    });
});
