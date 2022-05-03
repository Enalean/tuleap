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

import { NewLinksStore } from "./NewLinksStore";
import { LinkTypeStub } from "../../../tests/stubs/LinkTypeStub";
import { NewLinkStub } from "../../../tests/stubs/NewLinkStub";

describe(`NewLinksStore`, () => {
    it(`adds and deletes new links`, () => {
        const store = NewLinksStore();

        const first_link = NewLinkStub.withIdAndType(48, LinkTypeStub.buildUntyped());
        const second_link = NewLinkStub.withIdAndType(58, LinkTypeStub.buildChildLinkType());

        store.addNewLink(first_link);
        store.addNewLink(second_link);

        const stored_links = store.getNewLinks();
        expect(stored_links).toHaveLength(2);
        expect(stored_links).toContain(first_link);
        expect(stored_links).toContain(second_link);

        store.deleteNewLink(first_link);

        const links_after_deletion = store.getNewLinks();
        expect(links_after_deletion).toHaveLength(1);
        expect(links_after_deletion).not.toContain(first_link);
    });
});
