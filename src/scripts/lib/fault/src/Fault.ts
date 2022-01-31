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

const FaultSymbol = Symbol("Fault");

/**
 * I hold a technical or business error that is not fatal and does not require the program to stop immediately.
 * The error must be recoverable, otherwise use `Error`.
 * For example: A network error prevents loading an external API.
 * Another example: A user does not have permission to make an action.
 */
export interface Fault extends Record<string, unknown> {
    [FaultSymbol]: true;
    getStackTraceAsString(): string;
    toString(): string;
    valueOf(): string;
}

const newFault = (message: string, error: Error | null = null): Fault => {
    /** Internal Error used only to record stack traces. It is never thrown */
    const _error = error ?? new Error();

    return {
        [FaultSymbol]: true,
        getStackTraceAsString: (): string => _error.stack ?? "",
        toString: (): string => message,
        valueOf: (): string => message,
    };
};

/**
 * `isFault` returns true if `param` is a Fault
 * @param param
 */
export const isFault = (param: unknown): param is Fault =>
    Object.getOwnPropertySymbols(param).includes(FaultSymbol);

export const Fault = {
    /**
     * `fromMessage` returns a new Fault with the supplied message. It also records the stack trace at the point it was called.
     * @param message A message to explain what happened. This message could appear in console logs or on-screen.
     */
    fromMessage(message: string): Fault {
        return newFault(message);
    },

    /**
     * `fromError` wraps an existing Error and returns a new Fault with its message and stack trace.
     * It preserves both the message and the stack trace from `error`.
     * @param error Wrapped error
     */
    fromError(error: Error): Fault {
        return newFault(error.message, error);
    },

    /**
     * `fromErrorWithMessage` wraps an existing Error and returns a new Fault with the supplied message.
     * It discards the message from `error`. It preserves the stack trace from `error`.
     * @param error Wrapped error
     * @param message A message to explain what happened. This message could appear in console logs or on-screen.
     */
    fromErrorWithMessage(error: Error, message: string): Fault {
        return newFault(message, error);
    },
};
