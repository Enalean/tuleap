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
import type { HostElement } from "./LinkField";
import { getEmptyStateIfNeeded, getSkeletonIfNeeded, LinkField } from "./LinkField";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { Fault } from "@tuleap/fault";

const getDocument = (): Document => document.implementation.createHTMLDocument();

function getHost(data?: Partial<LinkField>): HostElement {
    return {
        fieldId: 60,
        label: "Links overview",
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

        it("should hide the table and show an alert when an error occurred during the retrieval of the linked artifacts", () => {
            const error_message = "Unable to retrieve the linked artifacts because reasons";
            const presenter = LinkFieldPresenter.fromFault(Fault.fromMessage(error_message));
            const host = getHost({ presenter });
            const update = LinkField.content(host);
            update(host, target);

            const table = target.querySelector("[data-test=linked-artifacts-table]");
            const error = target.querySelector("[data-test=linked-artifacts-error]");

            if (!(table instanceof HTMLElement) || !(error instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(table.classList.contains("tuleap-artifact-modal-link-field-empty")).toBe(true);
            expect(error.textContent?.trim()).toContain(error_message);
        });

        it("should render a skeleton row when the links are being loaded", () => {
            const render = getSkeletonIfNeeded(LinkFieldPresenter.buildLoadingState());

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-field-table-skeleton]")).not.toBeNull();
        });

        it("should render an empty state row when content has been loaded and there is no link to display", () => {
            const render = getEmptyStateIfNeeded(LinkFieldPresenter.forCreationMode());

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-table-empty-state]")).not.toBeNull();
        });
    });
});
