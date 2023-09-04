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

import type { LinkType } from "../../src/domain/fields/link-field/LinkType";
import { NewLink } from "../../src/domain/fields/link-field/NewLink";
import { LinkableArtifactStub } from "./LinkableArtifactStub";
import { LinkTypeStub } from "./LinkTypeStub";

export const NewLinkStub = {
    withDefaults: (id?: number, data?: Partial<NewLink>): NewLink =>
        NewLink.fromLinkableArtifactAndType(
            LinkableArtifactStub.withDefaults({ ...data, id: id ?? 806 }),
            data?.link_type ?? LinkTypeStub.buildUntyped(),
        ),

    withIdAndType: (id: number, type: LinkType): NewLink =>
        NewLink.fromLinkableArtifactAndType(LinkableArtifactStub.withDefaults({ id }), type),
};
