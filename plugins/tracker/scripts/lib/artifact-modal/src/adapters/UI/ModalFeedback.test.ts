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

import type { HostElement } from "./ModalFeedback";
import { ModalFeedback } from "./ModalFeedback";
import { setCatalog } from "../../gettext-catalog";
import type { ParentFeedbackPresenter } from "../../domain/parent/ParentFeedbackPresenter";
import { buildEmpty, buildFromArtifact } from "../../domain/parent/ParentFeedbackPresenter";

const PARENT_ID = 86;
const PARENT_TITLE = "foreclaw";

describe(`ModalFeedback`, () => {
    let target: ShadowRoot, presenter: ParentFeedbackPresenter;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        presenter = buildFromArtifact({ id: PARENT_ID, title: PARENT_TITLE });
    });

    const renderTemplate = (): void => {
        const host = { presenter } as HostElement;
        const updateFunction = ModalFeedback.content(host);
        updateFunction(host, target);
    };

    it(`when there is a parent artifact, it will show its title`, () => {
        renderTemplate();
        expect(target.querySelector("[data-test=parent-feedback]")).not.toBeNull();
    });

    it(`when there is no parent, it will show nothing`, () => {
        presenter = buildEmpty();
        renderTemplate();
        expect(target.querySelector("[data-test=parent-feedback]")).toBeNull();
    });
});
