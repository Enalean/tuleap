<!--
  - Copyright (c) Enalean, 2018-2019. All Rights Reserved.
  -
  - This file is a part of Tuleap.
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
    <div class="document-new-item-type-selector">
        <div class="document-new-item-type"
             v-for="type of supported_types"
             v-bind:key="type.identifier"
             v-bind:class="{'document-new-item-type-checked': type.is_checked}"
             v-on:click="$emit('input', type.identifier)"
        >
            <i class="document-new-item-type-icon fa" v-bind:class="type.icon"></i>
            <span class="document-new-item-type-label">{{ type.label }}</span>
        </div>
    </div>
</template>
<script>
import {
    ICON_EMPTY,
    ICON_LINK,
    ICON_WIKI,
    TYPE_EMPTY,
    TYPE_LINK,
    TYPE_WIKI,
    TYPE_FILE,
    ICON_FILE
} from "../../../constants.js";
import { mapState } from "vuex";

export default {
    name: "TypeSelector",
    props: {
        value: String
    },
    computed: {
        ...mapState(["user_can_create_wiki"]),
        supported_types() {
            let types = [
                {
                    identifier: TYPE_LINK,
                    is_checked: this.value === TYPE_LINK,
                    label: this.$gettext("Link"),
                    icon: ICON_LINK
                },
                {
                    identifier: TYPE_EMPTY,
                    is_checked: this.value === TYPE_EMPTY,
                    label: this.$gettext("Empty"),
                    icon: ICON_EMPTY
                },
                {
                    identifier: TYPE_FILE,
                    is_checked: this.value === TYPE_FILE,
                    label: this.$gettext("File"),
                    icon: ICON_FILE
                }
            ];
            if (this.user_can_create_wiki) {
                types.push({
                    identifier: TYPE_WIKI,
                    is_checked: this.value === TYPE_WIKI,
                    label: this.$gettext("Wiki page"),
                    icon: ICON_WIKI
                });
            }
            return types;
        }
    }
};
</script>
