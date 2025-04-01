/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it } from "vitest";
import type { Events } from "./helpers/widget-events";
import { UPDATE_WIDGET_TITLE_EVENT } from "./helpers/widget-events";
import type { Emitter } from "mitt";
import mitt from "mitt";
import { WidgetTitleUpdater } from "./WidgetTitleUpdater";

describe("WidgetTitleUpdater", () => {
    let emitter: Emitter<Events>;
    let widget_title_element: HTMLElement;

    beforeEach(() => {
        emitter = mitt<Events>();
        widget_title_element = document.createElement("span");
        widget_title_element.textContent = "Cross trackers search";
    });

    const getWidgetTitleUpdater = (): WidgetTitleUpdater => {
        const widget_title_updater = WidgetTitleUpdater(emitter, widget_title_element);
        widget_title_updater.listenToUpdateTitle();

        return widget_title_updater;
    };

    it("Changes the widget title according to the UPDATE_WIDGET_TITLE_EVENT", () => {
        const new_title = "Switched Query";

        getWidgetTitleUpdater();

        expect(widget_title_element.textContent).toBe("Cross trackers search");

        emitter.emit(UPDATE_WIDGET_TITLE_EVENT, { new_title });
        expect(widget_title_element.textContent).toBe("Switched Query");
    });
});
