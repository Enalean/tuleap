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

export const formatFilePathForUIRouter = (file_path: string): string => {
    // See https://github.com/angular-ui/ui-router/blob/461d7b2499ed95c59b325d7964fdc240367055a9/src/locationServices.ts#L30
    return file_path.replace(/(~|\/)/g, (matched_character: string) => {
        switch (matched_character) {
            case "~":
                return "~~";
            case "/":
                return "~2F";
            default:
                return "";
        }
    });
};
