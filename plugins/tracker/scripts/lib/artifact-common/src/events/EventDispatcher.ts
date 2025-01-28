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

type EventKey = string;

export interface DomainEvent<EventType extends EventKey> {
    readonly type: EventType;
}

export type EventsMap = {
    [Key in EventKey]: DomainEvent<Key>;
};

type EventHandler<HandledEvent> = (event: HandledEvent) => void;

export type DispatchEvents<AllEvents extends EventsMap> = {
    /**
     * Calls all observers of each event. There might be no observer. Events might be mutated by observers.
     */
    dispatch(
        event: AllEvents[keyof AllEvents],
        ...other_events: ReadonlyArray<AllEvents[keyof AllEvents]>
    ): void;
};

export type ObserveEvents<AllEvents extends EventsMap> = {
    addObserver<TypeOfEvent extends keyof AllEvents>(
        type: TypeOfEvent,
        callback: EventHandler<AllEvents[TypeOfEvent]>,
    ): void;
    removeObserver<TypeOfEvent extends keyof AllEvents>(
        type: TypeOfEvent,
        callback: EventHandler<AllEvents[TypeOfEvent]>,
    ): void;
};

export type EventDispatcher<AllEvents extends EventsMap> = DispatchEvents<AllEvents> &
    ObserveEvents<AllEvents>;

export const EventDispatcher = <AllEvents extends EventsMap>(): EventDispatcher<AllEvents> => {
    const event_observers = new Map();

    return {
        addObserver(type, callback): void {
            const set_of_observers = event_observers.get(type) ?? new Set();
            set_of_observers.add(callback);
            event_observers.set(type, set_of_observers);
        },

        removeObserver(type, callback): void {
            const set_of_observers = event_observers.get(type);
            if (!set_of_observers) {
                return;
            }
            set_of_observers.delete(callback);
        },

        dispatch(event, ...other_events): void {
            for (const current_event of [event, ...other_events]) {
                const set_of_observers = event_observers.get(current_event.type);
                if (!set_of_observers) {
                    return;
                }
                set_of_observers.forEach(
                    (callback: (event: AllEvents[keyof AllEvents]) => void) => {
                        callback(current_event);
                    },
                );
            }
        },
    };
};
