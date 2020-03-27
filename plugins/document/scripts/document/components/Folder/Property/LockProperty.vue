<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  -->

<template>
    <div class="tlp-form-element docman-item-lock-update">
        <label class="tlp-label" for="docman-version-update-lock" data-test="lock-property-label">
            {{ lock_label }}
        </label>
        <div class="tlp-switch">
            <input
                type="checkbox"
                id="docman-version-update-lock"
                class="tlp-switch-checkbox"
                name="is_file_locked"
                data-test="lock-property-input-switch"
                v-on:input="$emit('input', $event.target.checked)"
                v-bind:checked="is_checked"
            />
            <label for="docman-version-update-lock" class="tlp-switch-button" aria-hidden></label>
        </div>
    </div>
</template>

<script>
export default {
    name: "LockProperty",
    props: {
        item: Object,
    },
    computed: {
        is_checked() {
            return this.item.lock_info !== null;
        },
        lock_label() {
            return this.is_checked
                ? this.$gettext("Keep lock?")
                : this.$gettext("Lock new version");
        },
    },
};
</script>
