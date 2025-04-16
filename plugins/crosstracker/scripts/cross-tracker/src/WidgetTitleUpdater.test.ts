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
import {
    CREATE_NEW_QUERY_EVENT,
    EDIT_QUERY_EVENT,
    INITIALIZED_WITH_QUERY_EVENT,
    NEW_QUERY_CREATED_EVENT,
    QUERY_EDITED_EVENT,
    SWITCH_QUERY_EVENT,
} from "./helpers/widget-events";
import type { Emitter } from "mitt";
import mitt from "mitt";
import { WidgetTitleUpdater } from "./WidgetTitleUpdater";

describe("WidgetTitleUpdater", () => {
    const default_widget_title = "Cross trackers search";
    let emitter: Emitter<Events>;
    let widget_title_element: HTMLElement;

    beforeEach(() => {
        emitter = mitt<Events>();
        widget_title_element = document.createElement("span");
        widget_title_element.textContent = default_widget_title;
    });

    const getWidgetTitleUpdater = (): WidgetTitleUpdater => {
        const widget_title_updater = WidgetTitleUpdater(
            emitter,
            widget_title_element,
            default_widget_title,
        );
        widget_title_updater.listenToUpdateTitle();

        return widget_title_updater;
    };

    describe(`Reacts on
        CREATE_NEW_QUERY_EVENT,
        EDIT_QUERY_EVENT,
        INITIALIZED_WITH_QUERY_EVENT,
        SWITCH_QUERY_EVENT,
        NEW_QUERY_CREATED_EVENT,
        QUERY_EDITED_EVENT,
    `, () => {
        it("resets to the default title on CREATE_NEW_QUERY_EVENT", () => {
            getWidgetTitleUpdater();

            widget_title_element.textContent = "A not default title";
            emitter.emit(CREATE_NEW_QUERY_EVENT);

            expect(widget_title_element.textContent).toBe(default_widget_title);
        });

        it.each([
            EDIT_QUERY_EVENT,
            INITIALIZED_WITH_QUERY_EVENT,
            SWITCH_QUERY_EVENT,
            NEW_QUERY_CREATED_EVENT,
            QUERY_EDITED_EVENT,
        ])("updates the title on %s", (event: string) => {
            getWidgetTitleUpdater();

            const new_title = "Some artifacts";

            emitter.emit(event as keyof Events, {
                query: {
                    id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
                    tql_query: "SELECT @pretty_title FROM @project = 'self' WHERE @id > 15",
                    title: new_title,
                    description: "a query",
                    is_default: false,
                },
            });

            expect(widget_title_element.textContent).toBe(new_title);
        });
    });
});
