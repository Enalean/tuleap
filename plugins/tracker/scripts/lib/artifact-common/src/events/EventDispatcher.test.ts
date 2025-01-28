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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { Fault } from "@tuleap/fault";
import { EventDispatcher } from "./EventDispatcher";
import { WillNotifyFault } from "./WillNotifyFault";
import { WillEnableSubmit } from "./WillEnableSubmit";
import type { CommonEvents } from "./CommonEvents";

const FIRST_EVENT_TYPE = "WillEnableSubmit",
    SECOND_EVENT_TYPE = "WillNotifyFault";

describe(`EventDispatcher`, () => {
    let first_event: WillEnableSubmit,
        second_event: WillNotifyFault,
        dispatcher: EventDispatcher<CommonEvents>;

    beforeEach(() => {
        first_event = WillEnableSubmit();
        second_event = WillNotifyFault(Fault.fromMessage("Oooops"));
        dispatcher = EventDispatcher();
    });

    it(`does nothing when no observers have been added`, () => {
        const observer = vi.fn();
        dispatcher.dispatch(first_event);

        expect(observer).not.toHaveBeenCalled();
    });

    it(`does nothing when dispatching an event but observers for another event have been added`, () => {
        const other_observer = vi.fn();
        dispatcher.addObserver(SECOND_EVENT_TYPE, other_observer);
        dispatcher.dispatch(first_event);

        expect(other_observer).not.toHaveBeenCalled();
    });

    it(`dispatches an event to all its observers`, () => {
        const first_observer = vi.fn();
        const second_observer = vi.fn();
        dispatcher.addObserver(FIRST_EVENT_TYPE, first_observer);
        dispatcher.addObserver(FIRST_EVENT_TYPE, second_observer);
        dispatcher.dispatch(first_event);

        expect(first_observer).toHaveBeenCalledWith(first_event);
        expect(second_observer).toHaveBeenCalledWith(first_event);
    });

    it(`does nothing when told to remove an observer that was never added`, () => {
        const first_observer = (): void => {
            //Do nothing
        };
        expect(() => {
            dispatcher.removeObserver(FIRST_EVENT_TYPE, first_observer);
        }).not.toThrow();
    });

    it(`does nothing when dispatching an event after observers have been removed`, () => {
        const first_observer = vi.fn();
        const second_observer = vi.fn();
        dispatcher.addObserver(FIRST_EVENT_TYPE, first_observer);
        dispatcher.addObserver(FIRST_EVENT_TYPE, second_observer);
        dispatcher.removeObserver(FIRST_EVENT_TYPE, first_observer);
        dispatcher.removeObserver(FIRST_EVENT_TYPE, second_observer);
        dispatcher.dispatch(first_event);

        expect(first_observer).not.toHaveBeenCalled();
        expect(second_observer).not.toHaveBeenCalled();
    });

    it(`dispatches several events to all their respective observers`, () => {
        const first_observer = vi.fn();
        const second_observer = vi.fn();
        const third_observer = vi.fn();
        const fourth_observer = vi.fn();
        dispatcher.addObserver(FIRST_EVENT_TYPE, first_observer);
        dispatcher.addObserver(FIRST_EVENT_TYPE, second_observer);
        dispatcher.addObserver(SECOND_EVENT_TYPE, third_observer);
        dispatcher.addObserver(SECOND_EVENT_TYPE, fourth_observer);
        dispatcher.dispatch(first_event, second_event);

        expect(first_observer).toHaveBeenCalledWith(first_event);
        expect(second_observer).toHaveBeenCalledWith(first_event);
        expect(third_observer).toHaveBeenCalledWith(second_event);
        expect(fourth_observer).toHaveBeenCalledWith(second_event);
    });
});
