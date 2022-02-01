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
import type { LinkType } from "../../../../domain/fields/link-field-v2/LinkedArtifact";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";

const ARTIFACT_ID = 43;
const TITLE = "divinity";
const STATUS = "Todo";
const URI = "/plugins/tracker?aid=" + ARTIFACT_ID;
const TRACKER_SHORTNAME = "stories";
const COLOR = "graffiti-yellow";
const CROSS_REFERENCE = `${TRACKER_SHORTNAME} #${ARTIFACT_ID}`;

describe(`LinkedArtifactPresenter`, () => {
    it(`builds from a LinkedArtifact and adds marked for removal property`, () => {
        const link_type: LinkType = {
            shortname: "_is_child",
            direction: "forward",
            label: "Parent",
        };
        const linked_artifact = LinkedArtifactStub.withDefaults({
            identifier: LinkedArtifactIdentifierStub.withId(ARTIFACT_ID),
            title: TITLE,
            status: STATUS,
            is_open: true,
            uri: URI,
            xref: CROSS_REFERENCE,
            tracker: { color_name: COLOR },
            link_type,
        });

        const presenter = LinkedArtifactPresenter.fromLinkedArtifact(linked_artifact, true);

        expect(presenter.identifier.id).toBe(ARTIFACT_ID);
        expect(presenter.title).toBe(TITLE);
        expect(presenter.status).toBe(STATUS);
        expect(presenter.is_open).toBe(true);
        expect(presenter.uri).toBe(URI);
        expect(presenter.xref).toBe(CROSS_REFERENCE);
        expect(presenter.link_type).toEqual(link_type);
        expect(presenter.tracker.color_name).toBe(COLOR);
        expect(presenter.is_marked_for_removal).toBe(true);
    });
});
