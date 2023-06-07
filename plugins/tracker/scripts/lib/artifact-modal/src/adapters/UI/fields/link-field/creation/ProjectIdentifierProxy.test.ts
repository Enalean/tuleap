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

const VALUE = "110";

describe("ProjectIdentifierProxy", () => {
    let project_identifier: Option<ProjectIdentifier>;
    const triggerEvent = (): void => {
        const doc = document.implementation.createHTMLDocument();
        const select = doc.createElement("select");
        const selected_option = doc.createElement("option");
        selected_option.selected = true;
        selected_option.label = "My label";
        selected_option.value = VALUE;
        select.append(selected_option);

        select.addEventListener("change", (event): void => {
            project_identifier = ProjectIdentifierProxy.fromChangeEvent(event);
        });
        select.dispatchEvent(new Event("change"));
    };

    it("build from the change event on project select", () => {
        triggerEvent();
        expect(project_identifier.isValue()).toBe(true);
        expect(project_identifier.unwrapOr(null)?.id).toBe(Number(VALUE));
    });

    it("build nothing if the element is not a select", () => {
        const doc = document.implementation.createHTMLDocument();
        const input = doc.createElement("input");
        input.addEventListener("change", (event): void => {
            project_identifier = ProjectIdentifierProxy.fromChangeEvent(event);
        });
        input.dispatchEvent(new Event("change"));
        expect(project_identifier.isNothing()).toBe(true);
    });
});
