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

import { LinksStore } from "./LinksStore";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";

describe(`LinksStore`, () => {
    it(`adds, retrieves, and changes types of links`, () => {
        const store = LinksStore();

        const first_link = LinkedArtifactStub.withIdAndType(90, LinkTypeStub.buildUntyped());
        const second_link = LinkedArtifactStub.withIdAndType(
            55,
            LinkTypeStub.buildParentLinkType(),
        );

        store.addLinkedArtifacts([first_link, second_link]);

        const stored_links = store.getLinkedArtifacts();
        expect(stored_links).toHaveLength(2);
        expect(stored_links).toContain(first_link);
        expect(stored_links).toContain(second_link);

        store.changeLinkType(second_link, LinkTypeStub.buildUntyped());
        const links_after_update = store.getLinkedArtifacts();
        expect(links_after_update).toHaveLength(2);
        expect(links_after_update).toContain(first_link);
        expect(LinkType.isUntypedLink(links_after_update[1].link_type)).toBe(true);
    });

    it(`does not update links that were never added to the store`, () => {
        const store = LinksStore();

        const non_existing_link = LinkedArtifactStub.withIdAndType(18, LinkTypeStub.buildUntyped());

        store.changeLinkType(non_existing_link, LinkTypeStub.buildChildLinkType());
        const stored_links = store.getLinkedArtifacts();
        expect(stored_links).toHaveLength(0);
    });
});
