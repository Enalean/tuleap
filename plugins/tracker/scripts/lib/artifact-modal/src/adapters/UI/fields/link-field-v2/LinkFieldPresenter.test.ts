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

import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";

const FIELD_ID = 920;
const FIELD_LABEL = "Artifact link";

describe(`LinkFieldPresenter`, () => {
    let cross_reference: ArtifactCrossReference | null;

    beforeEach(() => {
        cross_reference = ArtifactCrossReferenceStub.withRef("story #62");
    });

    const build = (): LinkFieldPresenter =>
        LinkFieldPresenter.fromFieldAndCrossReference(
            {
                field_id: FIELD_ID,
                label: FIELD_LABEL,
                type: "art_link",
                allowed_types: [
                    {
                        shortname: IS_CHILD_LINK_TYPE,
                        forward_label: "Parent",
                        reverse_label: "Child",
                    },
                    {
                        shortname: "custom",
                        forward_label: "Custom Forward",
                        reverse_label: "Custom Reverse",
                    },
                ],
            },
            cross_reference
        );

    it(`keeps only "is_child" link type and builds from a field representation and the current artifact's cross-reference`, () => {
        const presenter = build();

        expect(presenter.field_id).toBe(FIELD_ID);
        expect(presenter.label).toBe(FIELD_LABEL);
        expect(presenter.current_artifact_reference?.ref).toBe("story #62");
        expect(presenter.allowed_types).toHaveLength(1);
        expect(presenter.allowed_types[0].forward_type_presenter.shortname).toBe(
            IS_CHILD_LINK_TYPE
        );
    });

    it(`builds with a null cross reference (creation mode)`, () => {
        cross_reference = null;
        const presenter = build();

        expect(presenter.current_artifact_reference).toBeNull();
    });
});
