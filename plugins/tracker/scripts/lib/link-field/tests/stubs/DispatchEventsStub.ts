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

import type { CommonEvents, DispatchEvents } from "@tuleap/plugin-tracker-artifact-common";

type EventType = keyof CommonEvents;

export type DispatchEventsStub = DispatchEvents<CommonEvents> & {
    getDispatchedEventTypes(): EventType[];
};

export const DispatchEventsStub = {
    buildNoOp: (): DispatchEvents<CommonEvents> => ({
        dispatch(): void {
            // Do nothing, ignore all events
        },
    }),

    withRecordOfEventTypes: (): DispatchEventsStub => {
        const event_types: EventType[] = [];
        return {
            dispatch(event, ...other_events): void {
                const current_event_types = [event, ...other_events].map(
                    (mapped_event) => mapped_event.type,
                );
                event_types.push(...current_event_types);
            },
            getDispatchedEventTypes: () => event_types,
        };
    },
};
