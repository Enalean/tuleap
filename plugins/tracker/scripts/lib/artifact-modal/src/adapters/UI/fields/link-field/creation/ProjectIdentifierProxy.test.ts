/*
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { ProjectIdentifierProxy } from "./ProjectIdentifierProxy";
import type { ProjectIdentifier } from "../../../../../domain/ProjectIdentifier";
import type { Option } from "@tuleap/option";

describe("ProjectIdentifierProxy", () => {
    let project_identifier: Option<ProjectIdentifier>, doc: Document, option: string;
    const PROJECT_ID = 110;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        option = `<option selected value="${PROJECT_ID}"></option>`;
    });

    const triggerEvent = (): void => {
        const select = doc.createElement("select");
        select.insertAdjacentHTML("afterbegin", option);
        select.addEventListener("change", (event) => {
            project_identifier = ProjectIdentifierProxy.fromChangeEvent(event);
        });
        select.dispatchEvent(new Event("change"));
    };

    it("builds from the change event on project select", () => {
        triggerEvent();
        expect(project_identifier.unwrapOr(null)?.id).toBe(PROJECT_ID);
    });

    it("builds nothing if the element is not a select", () => {
        const input = doc.createElement("input");
        input.addEventListener("change", (event) => {
            project_identifier = ProjectIdentifierProxy.fromChangeEvent(event);
        });
        input.dispatchEvent(new Event("change"));
        expect(project_identifier.isNothing()).toBe(true);
    });

    it(`builds nothing when the option's value is empty`, () => {
        option = `<option value=""></option>`;
        triggerEvent();
        expect(project_identifier.isNothing()).toBe(true);
    });
});
