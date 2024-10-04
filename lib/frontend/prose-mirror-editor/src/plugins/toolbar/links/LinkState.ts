/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { LinkProperties } from "../../../types/internal-types";

export type LinkState = {
    is_activated: boolean;
    is_disabled: boolean;
    link_href: string;
    link_title: string;
};

export const LinkState = {
    disabled: (): LinkState => ({
        is_activated: false,
        is_disabled: true,
        link_title: "",
        link_href: "",
    }),
    forLinkEdition: (link: LinkProperties): LinkState => ({
        is_activated: true,
        is_disabled: false,
        link_href: link.href,
        link_title: link.title,
    }),
    forLinkCreation: (link_title: string): LinkState => ({
        is_activated: false,
        is_disabled: false,
        link_href: "",
        link_title: link_title,
    }),
};
