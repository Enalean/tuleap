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

import { describe, expect, it } from "vitest";
import { NewLinksStore } from "./NewLinksStore";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";
import { NewLinkStub } from "../../../tests/stubs/links/NewLinkStub";
import { LinkType } from "../../domain/links/LinkType";

describe(`NewLinksStore`, () => {
    it(`adds, changes types of new links, and deletes new links`, () => {
        const store = NewLinksStore();

        const first_link = NewLinkStub.withIdAndType(48, LinkTypeStub.buildDefaultLinkType());
        const second_link = NewLinkStub.withIdAndType(58, LinkTypeStub.buildParentLinkType());

        store.addNewLink(first_link);
        store.addNewLink(second_link);

        const stored_links = store.getNewLinks();
        expect(stored_links).toHaveLength(2);
        expect(stored_links).toContain(first_link);
        expect(stored_links).toContain(second_link);

        store.changeNewLinkType(second_link, LinkTypeStub.buildDefaultLinkType());
        const links_after_update = store.getNewLinks();
        expect(links_after_update).toHaveLength(2);
        expect(links_after_update).toContain(first_link);
        expect(LinkType.isDefaultTypeLabel(links_after_update[1].link_type)).toBe(true);

        store.deleteNewLink(first_link);

        const links_after_deletion = store.getNewLinks();
        expect(links_after_deletion).toHaveLength(1);
        expect(links_after_deletion).not.toContain(first_link);
    });

    it(`does not update new links that were never added to the store`, () => {
        const store = NewLinksStore();

        const non_existing_link = NewLinkStub.withIdAndType(
            54,
            LinkTypeStub.buildDefaultLinkType(),
        );

        store.changeNewLinkType(non_existing_link, LinkTypeStub.buildChildLinkType());
        const stored_links = store.getNewLinks();
        expect(stored_links).toHaveLength(0);
    });
});
