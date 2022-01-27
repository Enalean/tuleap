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

import { LinksRetriever } from "./LinksRetriever";
import { StubWithTypes } from "../../../../tests/stubs/RetrieveLinkTypesStub";
import { StubWithSuccessiveLinkedArtifacts } from "../../../../tests/stubs/RetrieveLinkedArtifactsByTypeStub";
import type { LinkedArtifact, LinkType } from "./LinkedArtifact";

describe(`LinksRetriever`, () => {
    let parent_type: LinkType,
        child_type: LinkType,
        first_parent: LinkedArtifact,
        second_parent: LinkedArtifact,
        first_child: LinkedArtifact,
        second_child: LinkedArtifact;

    const getLinkedArtifacts = (): Promise<LinkedArtifact[]> => {
        parent_type = {
            shortname: "_is_child",
            direction: "forward",
            label: "Parent",
        };
        child_type = {
            shortname: "_is_child",
            direction: "reverse",
            label: "Child",
        };
        first_parent = {
            title: "A parent",
            link_type: child_type,
        } as LinkedArtifact;
        second_parent = {
            title: "Another parent",
            link_type: child_type,
        } as LinkedArtifact;
        first_child = {
            title: "A child",
            link_type: parent_type,
        } as LinkedArtifact;
        second_child = {
            title: "Another child",
            link_type: parent_type,
        } as LinkedArtifact;

        const retriever = LinksRetriever(
            StubWithTypes(parent_type, child_type),
            StubWithSuccessiveLinkedArtifacts(
                [first_child, second_child],
                [first_parent, second_parent]
            )
        );
        return retriever.getLinkedArtifacts(64);
    };

    it(`fetches all types of links from the given artifact id
        and will return all artifacts linked to it`, async () => {
        const artifacts = await getLinkedArtifacts();
        expect(artifacts).toHaveLength(4);
        expect(artifacts).toContain(first_child);
        expect(artifacts).toContain(second_child);
        expect(artifacts).toContain(first_parent);
        expect(artifacts).toContain(second_parent);
    });
});
