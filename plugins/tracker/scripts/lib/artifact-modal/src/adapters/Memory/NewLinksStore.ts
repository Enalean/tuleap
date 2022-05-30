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

import type { AddNewLink } from "../../domain/fields/link-field/AddNewLink";
import type { NewLink } from "../../domain/fields/link-field/NewLink";
import type { RetrieveNewLinks } from "../../domain/fields/link-field/RetrieveNewLinks";
import type { DeleteNewLink } from "../../domain/fields/link-field/DeleteNewLink";

type NewLinksStoreType = AddNewLink & RetrieveNewLinks & DeleteNewLink;

export const NewLinksStore = (): NewLinksStoreType => {
    let links: NewLink[] = [];

    return {
        addNewLink(link: NewLink): void {
            links.push(link);
        },

        getNewLinks(): ReadonlyArray<NewLink> {
            return links;
        },

        deleteNewLink(link: NewLink): void {
            links = links.filter((stored_link) => link !== stored_link);
        },
    };
};
