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

import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./ModalFeedback";
import { ModalFeedback } from "./ModalFeedback";
import { setCatalog } from "../../../gettext-catalog";
import type { ParentArtifact } from "../../../domain/parent/ParentArtifact";
import { FaultFeedbackPresenter } from "./FaultFeedbackPresenter";

const PARENT_TITLE = "foreclaw";
const FAULT_MESSAGE = "An error occurred";

describe(`ModalFeedback`, () => {
    let target: ShadowRoot,
        parent_option: Option<ParentArtifact>,
        fault_presenter: FaultFeedbackPresenter;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        parent_option = Option.fromValue({ title: PARENT_TITLE });
        fault_presenter = FaultFeedbackPresenter.fromFault(Fault.fromMessage(FAULT_MESSAGE));
    });

    const renderTemplate = (): void => {
        const host = { parent_option, fault_presenter } as HostElement;
        const updateFunction = ModalFeedback.content(host);
        updateFunction(host, target);
    };

    it(`when there is a parent artifact, it will show its title`, () => {
        renderTemplate();
        expect(target.querySelector("[data-test=parent-feedback]")).not.toBeNull();
    });

    it(`when there is no parent, it will show nothing`, () => {
        parent_option = Option.nothing();
        renderTemplate();
        expect(target.querySelector("[data-test=parent-feedback]")).toBeNull();
    });

    it(`when there is a fault, it will show it`, () => {
        renderTemplate();
        const fault_template = selectOrThrow(target, "[data-test=fault-feedback]");
        expect(fault_template.textContent?.trim()).toBe(FAULT_MESSAGE);
    });

    it(`when there is no fault, it will show nothing`, () => {
        fault_presenter = FaultFeedbackPresenter.buildEmpty();
        renderTemplate();
        expect(target.querySelector("[data-test=fault-feedback]")).toBeNull();
    });

    it(`when there is neither parent nor fault, it will show nothing`, () => {
        parent_option = Option.nothing();
        fault_presenter = FaultFeedbackPresenter.buildEmpty();
        renderTemplate();
        expect(target.childElementCount).toBe(0);
    });
});
