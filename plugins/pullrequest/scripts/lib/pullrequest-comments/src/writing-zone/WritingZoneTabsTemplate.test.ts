/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { WritingZonePresenter } from "./WritingZonePresenter";
import type { HostElement } from "./WritingZone";
import { buildWriteTab } from "./WritingZoneTabsTemplate";
import type { ControlWritingZone } from "./WritingZoneController";
import { WritingZoneController } from "./WritingZoneController";

describe("WritingZoneTabsTemplate", () => {
    let target: ShadowRoot, controller: ControlWritingZone;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        controller = WritingZoneController({
            focus_writing_zone_when_connected: false,
        });
    });

    const getWritingTab = (host: HostElement): HTMLElement => {
        const render = buildWriteTab(host, GettextProviderStub);
        render(host, target);

        return selectOrThrow(target, "[data-test=writing-tab]");
    };

    it("should be active by default when the WritingZone has the focus", () => {
        const tab = getWritingTab({
            controller,
            presenter: WritingZonePresenter.buildFocused(WritingZonePresenter.buildInitial()),
        } as HostElement);

        expect(Array.from(tab.classList)).toStrictEqual(["tlp-tab", "tlp-tab-active"]);
    });

    it("should not be active when the WritingZone has not the focus", () => {
        const tab = getWritingTab({
            controller,
            presenter: WritingZonePresenter.buildBlurred(WritingZonePresenter.buildInitial()),
        } as HostElement);

        expect(Array.from(tab.classList)).toStrictEqual(["tlp-tab"]);
    });

    it("When it is clicked, the writing mode should be activated", () => {
        const switchToWritingMode = vi.spyOn(controller, "switchToWritingMode");
        const tab = getWritingTab({
            controller,
            presenter: WritingZonePresenter.buildBlurred(WritingZonePresenter.buildInitial()),
        } as HostElement);

        tab.click();

        expect(switchToWritingMode).toHaveBeenCalledOnce();
    });
});
