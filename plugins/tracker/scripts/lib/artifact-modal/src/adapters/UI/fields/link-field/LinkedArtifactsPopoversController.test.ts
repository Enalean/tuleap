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

import * as tlp from "tlp";
import { LinkedArtifactsPopoversController } from "./LinkedArtifactsPopoversController";

const emptyFunction = (): void => {
    //Do nothing
};

describe("LinkedArtifactsPopoversController", () => {
    it("Given a collection of popover elements, then it should create tlp Popovers", () => {
        const fake_popover = {
            destroy: emptyFunction,
            hide: emptyFunction,
        };
        jest.spyOn(tlp, "createPopover").mockReturnValue(fake_popover);

        const doc = document.implementation.createHTMLDocument();
        const popover_1 = {
            popover_trigger: doc.createElement("button"),
            popover_content: doc.createElement("section"),
        };

        const popover_2 = {
            popover_trigger: doc.createElement("button"),
            popover_content: doc.createElement("section"),
        };

        const controller = LinkedArtifactsPopoversController();

        controller.initPopovers([popover_1, popover_2]);

        expect(tlp.createPopover).toHaveBeenCalledTimes(2);
    });
});
