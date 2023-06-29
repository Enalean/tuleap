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

import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../../../../tests/stubs/LinkedArtifactIdentifierStub";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";

const ARTIFACT_ID = 43;
const TITLE = "divinity";
const STATUS = "Todo";
const URI = "/plugins/tracker?aid=" + ARTIFACT_ID;
const TRACKER_SHORTNAME = "stories";
const COLOR = "graffiti-yellow";
const CROSS_REFERENCE = `${TRACKER_SHORTNAME} #${ARTIFACT_ID}`;

describe(`LinkedArtifactPresenter`, () => {
    it(`builds from a LinkedArtifact and adds parent and marked for removal property`, () => {
        const link_type = LinkTypeStub.buildForwardCustom();
        const linked_artifact = LinkedArtifactStub.withDefaults({
            identifier: LinkedArtifactIdentifierStub.withId(ARTIFACT_ID),
            title: TITLE,
            status: { value: STATUS, color: null },
            is_open: true,
            uri: URI,
            xref: ArtifactCrossReferenceStub.withRefAndColor(CROSS_REFERENCE, COLOR),
            link_type,
        });

        const presenter = LinkedArtifactPresenter.fromLinkedArtifact(linked_artifact, false, true);

        expect(presenter.identifier.id).toBe(ARTIFACT_ID);
        expect(presenter.title).toBe(TITLE);
        expect(presenter.status?.value).toBe(STATUS);
        expect(presenter.is_open).toBe(true);
        expect(presenter.uri).toBe(URI);
        expect(presenter.xref.ref).toBe(CROSS_REFERENCE);
        expect(presenter.xref.color).toBe(COLOR);
        expect(presenter.link_type).toStrictEqual(link_type);
        expect(presenter.is_parent).toBe(false);
        expect(presenter.is_marked_for_removal).toBe(true);
    });
});
