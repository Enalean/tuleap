<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <tr>
        <td class="tlp-table-cell-numeric">
            {{ item.id }}
        </td>
        <td class="document-search-result-icon">
            <i class="fa fa-fw" v-bind:class="icon_classes" aria-hidden="true"></i>
        </td>
        <td>
            <a v-if="href" v-bind:href="href" class="document-folder-subitem-link" data-test="link">
                {{ item.title }}
            </a>
            <router-link
                v-else-if="in_app_link"
                v-bind:to="in_app_link"
                class="document-folder-subitem-link"
                data-test="router-link"
            >
                {{ item.title }}
            </router-link>
            <template v-else>{{ item.title }}</template>
        </td>
        <td v-dompurify-html="item.post_processed_description"></td>
        <td>
            <user-badge v-bind:user="item.owner" />
        </td>
        <td>
            <tlp-relative-date
                v-bind:date="item.last_update_date"
                v-bind:absolute-date="formatted_full_date"
                v-bind:placement="relative_date_placement"
                v-bind:preference="relative_date_preference"
                v-bind:locale="user_locale"
            >
                {{ formatted_full_date }}
            </tlp-relative-date>
        </td>
        <td data-test="location">{{ location }}</td>
    </tr>
</template>
<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Folder, ItemSearchResult } from "../../../type";
import UserBadge from "../../User/UserBadge.vue";
import { formatDateUsingPreferredUserFormat } from "../../../helpers/date-formatter";
import { namespace, State } from "vuex-class";
import {
    relativeDatePlacement,
    relativeDatePreference,
} from "@tuleap/core/scripts/tuleap/custom-elements/relative-date/relative-date-helper";
import {
    ICON_EMBEDDED,
    ICON_EMPTY,
    ICON_FOLDER_ICON,
    ICON_LINK,
    ICON_WIKI,
    TYPE_EMBEDDED,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../../constants";
import { iconForMimeType } from "../../../helpers/icon-for-mime-type";
import type { Route } from "vue-router/types/router";

const configuration = namespace("configuration");

@Component({
    components: { UserBadge },
})
export default class TableBodyResultRow extends Vue {
    @Prop({ required: true })
    readonly item!: ItemSearchResult;

    @configuration.State
    readonly date_time_format!: string;

    @configuration.State
    readonly relative_dates_display!: string;

    @configuration.State
    readonly user_locale!: string;

    @configuration.State
    readonly project_id!: number;

    @State
    readonly current_folder!: Folder;

    get formatted_full_date(): string {
        return formatDateUsingPreferredUserFormat(
            String(this.item.last_update_date),
            this.date_time_format
        );
    }

    get relative_date_preference(): string {
        return relativeDatePreference(this.relative_dates_display);
    }

    get relative_date_placement(): string {
        return relativeDatePlacement(this.relative_dates_display, "top");
    }

    get location(): string {
        return this.item.parents.map((parent) => parent.title).join("/");
    }

    get icon_classes(): string {
        switch (this.item.type) {
            case TYPE_FILE:
                if (!this.item.file_properties) {
                    return ICON_EMPTY;
                }

                return iconForMimeType(this.item.file_properties.file_type);
            case TYPE_EMBEDDED:
                return ICON_EMBEDDED;
            case TYPE_FOLDER:
                return ICON_FOLDER_ICON;
            case TYPE_LINK:
                return ICON_LINK;
            case TYPE_WIKI:
                return ICON_WIKI;
            default:
                return ICON_EMPTY;
        }
    }

    get href(): string | null {
        if (this.item.type === TYPE_FILE && this.item.file_properties) {
            return this.item.file_properties.download_href;
        }

        if (this.item.type === TYPE_LINK || this.item.type === TYPE_WIKI) {
            return `/plugins/docman/?group_id=${this.project_id}&action=show&id=${this.item.id}`;
        }

        return null;
    }

    get in_app_link(): Partial<Route> | null {
        if (this.item.type === TYPE_EMBEDDED) {
            const item_id = String(this.item.id);
            const folder_id =
                this.item.parents.length > 0
                    ? String(this.item.parents[0].id)
                    : String(this.current_folder.id);

            return {
                name: "item",
                params: {
                    folder_id,
                    item_id,
                },
            };
        }

        if (this.item.type === TYPE_FOLDER) {
            return {
                name: "folder",
                params: {
                    item_id: String(this.item.id),
                },
            };
        }

        return null;
    }
}
</script>
