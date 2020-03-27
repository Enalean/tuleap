<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="document-new-item-type-selector">
        <div
            class="document-new-item-type"
            v-for="type of supported_types"
            v-bind:key="type.identifier"
            v-bind:class="{ 'document-new-item-type-checked': type.is_checked }"
            v-on:click="$emit('input', type.identifier)"
            v-bind:data-test="`document-type-selector-${type.identifier}`"
        >
            <i
                class="document-new-item-type-icon fa"
                v-for="icon of type.icons"
                v-bind:key="icon"
                v-bind:class="icon"
            ></i>
            <span class="document-new-item-type-label">{{ type.label }}</span>
        </div>
    </div>
</template>
<script>
import {
    ICON_EMBEDDED,
    ICON_LINK,
    TYPE_EMBEDDED,
    TYPE_LINK,
    TYPE_FILE,
} from "../../../constants.js";
import { mapState } from "vuex";

export default {
    name: "TypeSelectorForEmptyModal",
    props: {
        value: String,
    },
    computed: {
        ...mapState(["embedded_are_allowed"]),
        supported_types() {
            let types = [
                {
                    identifier: TYPE_FILE,
                    is_checked: this.value === TYPE_FILE,
                    label: this.$gettext("File"),
                    icons: [
                        "fa-file-excel-o",
                        "fa-file-word-o",
                        "fa-file-pdf-o",
                        "fa-file-image-o",
                    ],
                },
                {
                    identifier: TYPE_LINK,
                    is_checked: this.value === TYPE_LINK,
                    label: this.$gettext("Link"),
                    icons: [ICON_LINK],
                },
            ];
            if (this.embedded_are_allowed) {
                types.push({
                    identifier: TYPE_EMBEDDED,
                    is_checked: this.value === TYPE_EMBEDDED,
                    label: this.$gettext("Embedded"),
                    icons: [ICON_EMBEDDED],
                });
            }
            return types;
        },
    },
};
</script>
