/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { HostElement } from "./ArtifactCreatorElement";
import { ArtifactCreatorElement, observeIsLoading, onClick } from "./ArtifactCreatorElement";
import { ArtifactCreatorController } from "../../../../../domain/fields/link-field/creation/ArtifactCreatorController";
import { DispatchEventsStub } from "../../../../../../tests/stubs/DispatchEventsStub";
import { setCatalog } from "../../../../../gettext-catalog";
import { selectOrThrow } from "@tuleap/dom";

describe(`ArtifactCreatorElement`, () => {
    let doc: Document;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();
    });

    describe(`events`, () => {
        let controller: ArtifactCreatorController, dispatchEvent: jest.SpyInstance;
        beforeEach(() => {
            controller = ArtifactCreatorController(DispatchEventsStub.buildNoOp());
            dispatchEvent = jest.fn();
        });

        const getHost = (): HostElement => {
            const element = doc.createElement("span");
            return Object.assign(element, {
                is_loading: false,
                controller,
                dispatchEvent,
                content: () => element,
            }) as HostElement;
        };

        it(`when I click on the "Cancel" button, it will enable the modal submit
            and dispatch a "cancel" event`, () => {
            const host = getHost();
            const enableSubmit = jest.spyOn(controller, "enableSubmit");

            onClick(host);

            expect(enableSubmit).toHaveBeenCalled();
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("cancel");
        });

        it(`when is_loading becomes true, it will dispatch a "loadingchange" event
            and will disable the modal submit`, () => {
            const host = getHost();
            const disableSubmit = jest.spyOn(controller, "disableSubmit");

            observeIsLoading(host, true);

            expect(disableSubmit).toHaveBeenCalled();
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("loadingchange");
            expect(event.detail.is_loading).toBe(true);
        });

        it(`when is_loading becomes false, it will dispatch a "loadingchange" event
            and will enable the modal submit`, () => {
            const host = getHost();
            const enableSubmit = jest.spyOn(controller, "enableSubmit");

            observeIsLoading(host, false);

            expect(enableSubmit).toHaveBeenCalled();
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("loadingchange");
            expect(event.detail.is_loading).toBe(false);
        });
    });

    describe(`render`, () => {
        let target: ShadowRoot, is_loading: boolean;

        beforeEach(() => {
            target = doc.createElement("div") as unknown as ShadowRoot;
            is_loading = true;
        });
        const render = (): void => {
            const host = {
                is_loading,
            } as HostElement;
            const updateFunction = ArtifactCreatorElement.content(host);
            updateFunction(host, target);
        };

        it(`when it is loading, it will disable inputs and buttons and will show a spinner icon`, () => {
            render();
            const input = selectOrThrow(
                target,
                "[data-test=artifact-creator-title]",
                HTMLInputElement
            );
            const submit = selectOrThrow(
                target,
                "[data-test=artifact-creator-submit]",
                HTMLButtonElement
            );

            expect(input.disabled).toBe(true);
            expect(submit.disabled).toBe(true);
            expect(target.querySelector("[data-test=artifact-creator-spinner]")).toBeDefined();
        });
    });
});
