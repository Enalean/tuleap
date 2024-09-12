/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import { noop } from "@/helpers/noop";

type OpenConfigurationModalHandler = (onSuccessfulSaved: () => void) => void;

export interface OpenConfigurationModalBusStore {
    readonly registerHandler: (new_handler: OpenConfigurationModalHandler) => void;
    readonly openModal: (onSuccessfulSaved?: () => void) => void;
}

export const OPEN_CONFIGURATION_MODAL_BUS: StrictInjectionKey<OpenConfigurationModalBusStore> =
    Symbol("open_configuration_modal_bus");

export function useOpenConfigurationModalBusStore(): OpenConfigurationModalBusStore {
    let handler: OpenConfigurationModalHandler = noop;

    return {
        registerHandler: (new_handler: OpenConfigurationModalHandler): void => {
            handler = new_handler;
        },
        openModal: (onSuccessfulSaved?: () => void): void => {
            handler(onSuccessfulSaved || noop);
        },
    };
}
