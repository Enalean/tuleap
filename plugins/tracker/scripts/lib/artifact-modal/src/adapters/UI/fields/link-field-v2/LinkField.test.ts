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

import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement, LinkField } from "./LinkField";
import { getEmptyStateIfNeeded, getSkeletonIfNeeded } from "./LinkField";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";

const getDocument = (): Document => document.implementation.createHTMLDocument();

function getHost(data?: Partial<LinkField>): HostElement {
    return {
        fieldId: 60,
        label: "Links overview",
        allowedTypes: [],
        artifactCrossReference: ArtifactCrossReferenceStub.withRef("story #103"),
        ...data,
    } as unknown as HostElement;
}

describe("LinkField", () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    describe("Display", () => {
        let target: ShadowRoot;
        beforeEach(() => {
            target = getDocument().createElement("div") as unknown as ShadowRoot;
        });

        it("should render a skeleton row when the links are being loaded", () => {
            const render = getSkeletonIfNeeded(LinkFieldPresenter.buildLoadingState());

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-field-table-skeleton]")).not.toBeNull();
        });

        it("should render an empty state row when content has been loaded and there is no link to display", () => {
            const render = getEmptyStateIfNeeded(LinkFieldPresenter.forFault());

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-table-empty-state]")).not.toBeNull();
        });
    });
});
