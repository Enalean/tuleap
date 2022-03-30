<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <button
        v-on:click="$emit('click')"
        class="tlp-button-small tlp-button-primary tlp-button-outline"
        v-bind:disabled="disabled"
        data-test-type="button"
    >
        <i
            v-if="loading"
            class="fa fa-spinner fa-fw fa-spin tlp-button-icon"
            data-test-type="spinner"
        ></i>
        <i v-else class="fa fa-fw tlp-button-icon" v-bind:class="icon_class"></i>
        <slot />
    </button>
</template>

<script>
export default {
    name: "ActionButton",
    props: {
        icon: { required: true, type: String },
        loading: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
    },
    computed: {
        icon_class() {
            return `fa-${this.icon}`;
        },
    },
};
</script>
