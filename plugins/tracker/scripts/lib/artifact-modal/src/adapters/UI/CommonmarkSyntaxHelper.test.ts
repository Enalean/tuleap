/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import * as popover from "@tuleap/tlp-popovers";
import { connect, CommonmarkSyntaxHelper } from "./CommonmarkSyntaxHelper";
import { setCatalog } from "../../gettext-catalog";

const emptyFunction = (): void => {
    //Do nothing
};

type HostElement = CommonmarkSyntaxHelper & HTMLElement;

describe(`CommonmarkSyntaxHelper`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setCatalog({
            getString: (msgid) => msgid,
        });
    });

    describe(`render()`, () => {
        it(`when the component is disabled, the button will be disabled`, () => {
            const target = doc.createElement("div") as unknown as ShadowRoot;
            const host = {
                disabled: true,
                section: null,
                button: null,
            } as HostElement;
            const update = CommonmarkSyntaxHelper.content(host);
            update(host, target);

            const button = target.querySelector("[data-test=artifact-modal-helper-popover-button]");
            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("Expected to find the button in the template");
            }
            expect(button.disabled).toBe(true);
        });
    });

    describe(`disconnect()`, () => {
        it(`destroys the popover`, () => {
            const fake_popover = {
                destroy: emptyFunction,
                hide: emptyFunction,
            };
            const destroyPopover = jest.spyOn(fake_popover, "destroy");
            jest.spyOn(popover, "createPopover").mockReturnValue(fake_popover);
            const section: HTMLElement = doc.createElement("div");
            const host = {
                section,
                button: doc.createElement("button"),
            } as HostElement;

            const disconnect = connect(host);
            if (typeof disconnect !== "function") {
                throw new Error("Disconnect should be a function");
            }
            disconnect();

            expect(destroyPopover).toHaveBeenCalled();
        });
    });
});
