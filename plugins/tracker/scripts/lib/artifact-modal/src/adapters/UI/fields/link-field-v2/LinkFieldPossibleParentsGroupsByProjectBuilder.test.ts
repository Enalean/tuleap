/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { LinkFieldPossibleParentsGroupsByProjectBuilder } from "./LinkFieldPossibleParentsGroupsByProjectBuilder";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { setCatalog } from "../../../../gettext-catalog";

describe("LinkFieldPossibleParentsGroupsByProjectBuilder", () => {
    beforeEach(() => {
        setCatalog({
            getString: (msgid) => msgid,
        });
    });

    it(`Given a collection of linkable artifacts coming from different projects,
        Then it should return a Group collection`, () => {
        const project_185 = { id: 185, label: "Rhinoceros", icon: "🦏" };
        const project_300 = { id: 300, label: "Guinea Pig", icon: "🐹" };

        const artifact_1000 = LinkableArtifactStub.withDefaults({ id: 1000, project: project_185 });
        const artifact_1001 = LinkableArtifactStub.withDefaults({ id: 1001, project: project_300 });
        const artifact_1002 = LinkableArtifactStub.withDefaults({ id: 1002, project: project_300 });

        const group_collection =
            LinkFieldPossibleParentsGroupsByProjectBuilder.buildGroupsSortedByProject(
                VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked(),
                [artifact_1000, artifact_1001, artifact_1002]
            );

        expect(group_collection).toHaveLength(2);

        const first_group = group_collection[0];
        expect(first_group.label).toBe(project_185.label);
        expect(first_group.icon).toBe(project_185.icon);

        expect(first_group.items).toHaveLength(1);
        expect(first_group.items[0].value).toStrictEqual(artifact_1000);

        const second_group = group_collection[1];
        expect(second_group.label).toBe(project_300.label);
        expect(second_group.icon).toBe(project_300.icon);

        expect(second_group.items).toHaveLength(2);
        expect(second_group.items[0].value).toStrictEqual(artifact_1001);
        expect(second_group.items[1].value).toStrictEqual(artifact_1002);
    });
});
