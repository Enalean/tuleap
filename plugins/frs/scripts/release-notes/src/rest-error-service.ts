/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
export interface RestError {
    rest_error: string;
    rest_error_occurred: boolean;
}

export interface RestErrorResponse {
    code: number;
    message: {
        error: { code: number; message: string };
    };
}

export type RestErrorServiceStore = {
    getError(): RestError;
    setError(rest_error: RestErrorResponse): void;
};

export const RestErrorService = (): RestErrorServiceStore => {
    const error: RestError = {
        rest_error: "",
        rest_error_occurred: false,
    };

    return {
        getError(): RestError {
            return error;
        },

        setError(rest_error: RestErrorResponse): void {
            error.rest_error_occurred = true;
            error.rest_error = `${rest_error.code} ${JSON.stringify(rest_error.message.error.message)}`;
        },
    };
};
