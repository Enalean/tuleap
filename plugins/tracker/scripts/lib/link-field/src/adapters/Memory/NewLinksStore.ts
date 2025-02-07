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

import type { AddNewLink } from "../../domain/links/AddNewLink";
import { NewLink } from "../../domain/links/NewLink";
import type { RetrieveNewLinks } from "../../domain/links/RetrieveNewLinks";
import type { DeleteNewLink } from "../../domain/links/DeleteNewLink";
import type { ChangeNewLinkType } from "../../domain/links/ChangeNewLinkType";

export type NewLinksStore = AddNewLink & RetrieveNewLinks & DeleteNewLink & ChangeNewLinkType;

export const NewLinksStore = (): NewLinksStore => {
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

        changeNewLinkType(link, type): void {
            const updated_link = NewLink.fromNewLinkAndType(link, type);
            const index = links.findIndex(
                (stored_link) => stored_link.identifier.id === updated_link.identifier.id,
            );
            if (index === -1) {
                return;
            }
            links.splice(index, 1, updated_link);
        },
    };
};
