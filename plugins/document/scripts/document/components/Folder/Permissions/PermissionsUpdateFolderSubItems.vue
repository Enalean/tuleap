<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element" v-if="is_item_a_folder">
        <label class="tlp-label" for="document-permissions-update-children" v-translate>
            Apply same permissions to all sub-items of this folder
        </label>
        <div class="tlp-switch">
            <input
                type="checkbox"
                id="document-permissions-update-children"
                class="tlp-switch-checkbox"
                v-bind:value="value"
                v-on:input="$emit('input', $event.target.checked)"
            />
            <label
                class="tlp-switch-button"
                for="document-permissions-update-children"
                aria-hidden
            ></label>
        </div>
    </div>
</template>
<script>
import { TYPE_FOLDER } from "../../../constants";

export default {
    name: "PermissionsUpdateFolderSubItems",
    props: {
        item: Object,
        value: Boolean,
    },
    computed: {
        is_item_a_folder() {
            return this.item.type === TYPE_FOLDER;
        },
    },
};
</script>
