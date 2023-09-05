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

import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { getDatasetItemOrThrow, selectOrThrow } from "@tuleap/dom";
import { getPOFileFromLocaleWithoutExtension, initGettext } from "@tuleap/gettext";
import { EditConfigurationModal } from "./EditConfigurationModal";
import { UnlinkModal } from "./UnlinkModal";
import { SynchronizeButton } from "./SynchronizeButton";
import { TokenModal } from "./TokenModal";
import type { GroupInformation } from "./GroupInformation";
import "../themes/main.scss";

const UNLINK_SELECTOR = "#unlink-button";
const SECTION_SELECTOR = "#buttons-section";

document.addEventListener("DOMContentLoaded", async () => {
    const locale = getDatasetItemOrThrow(document.body, "userLocale");
    const gettext_provider = await initGettext(
        locale,
        "plugin-gitlab/linked-group",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const buttons_section = selectOrThrow(document.body, SECTION_SELECTOR);
    const group_id = Number.parseInt(getDatasetItemOrThrow(buttons_section, "groupId"), 10);
    const gitlab_server_uri = getDatasetItemOrThrow(buttons_section, "gitlabUri");
    const gitlab_group_id = Number.parseInt(
        getDatasetItemOrThrow(buttons_section, "gitlabGroupId"),
        10,
    );
    const group: GroupInformation = { id: group_id, gitlab_server_uri, gitlab_group_id };

    openAllTargetModalsOnClick(document, UNLINK_SELECTOR);

    SynchronizeButton(document, gettext_provider, group_id).init();
    EditConfigurationModal(document, gettext_provider, group_id).init();
    TokenModal(document, gettext_provider, group).init();
    UnlinkModal(document.location, document, gettext_provider, group_id).init();
});
