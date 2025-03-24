/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Events } from "../../src/helpers/widget-events";
import { Option } from "@tuleap/option";
import type { Emitter } from "mitt";

type EmittedEventTest = {
    emitted_event_name: Array<keyof Events>;
    emitted_event_message: Array<Option<Events[keyof Events]>>;
};

export type EmitterStub = Emitter<Events> & EmittedEventTest;

export const EmitterStub = (): EmitterStub => {
    const all = new Map();
    const emitted_event_name: Array<keyof Events> = [];
    const emitted_event_message: Array<Option<Events[keyof Events]>> = [];
    function off(): void {}
    function emit(type: keyof Events, event?: Events[keyof Events]): void {
        emitted_event_name.push(type);
        if (event === undefined) {
            emitted_event_message.push(Option.nothing());
            return;
        }
        emitted_event_message.push(Option.fromValue(event));
    }

    function on<Key extends keyof Events>(type: Key): void {
        emitted_event_name.push(type);
        emitted_event_message.push(Option.nothing());
    }
    return {
        emitted_event_name,
        emitted_event_message,
        all,
        off,
        emit,
        on,
    };
};
