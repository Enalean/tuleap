<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="tlp-table-actions">
        <div class="tlp-table-actions-spacer"></div>
        <div class="tlp-form-element tlp-table-actions-element">
            <input
                type="search"
                class="tlp-search"
                autocomplete="off"
                v-bind:placeholder="placeholder"
                v-bind:value="value"
                v-on:keyup="search"
            />
        </div>
    </div>
</template>

<script>
export default {
    name: "GitInlineFilter",
    props: {
        value: String,
    },
    computed: {
        placeholder() {
            return this.$gettext("Repository name");
        },
    },
    methods: {
        search(event) {
            this.$emit("input", event.target.value);
        },
    },
};
</script>
