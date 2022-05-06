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

interface BaseNewChangesetValue {
    readonly field_id: number;
}

interface UnknownNewChangesetValue extends BaseNewChangesetValue {
    readonly value: never;
}

type ArtifactLinkNewChangesetParent = {
    readonly id: number;
};

type ArtifactLinkVariantWithParent = {
    readonly parent: ArtifactLinkNewChangesetParent;
};

export type ArtifactLinkNewChangesetLink = {
    readonly id: number;
    readonly type?: string | null;
};

type ArtifactLinkVariantWithLinks = {
    readonly links: ReadonlyArray<ArtifactLinkNewChangesetLink>;
};

export type ArtifactLinkNewChangesetValue = BaseNewChangesetValue &
    (ArtifactLinkVariantWithParent | ArtifactLinkVariantWithLinks);

export type NewChangesetValue = UnknownNewChangesetValue | ArtifactLinkNewChangesetValue;
