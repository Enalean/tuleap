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
    <router-link
        class="tlp-button-primary tlp-button-large"
        v-bind:to="{ name: 'root_folder' }"
        v-if="can_go_to_root"
        data-test="item-can-go-to-root-button"
    >
        <i class="fa fa-reply tlp-button-icon"></i>
        <translate>Go to Document manager home page</translate>
    </router-link>
</template>

<script>
export default {
    computed: {
        can_go_to_root() {
            return this.$route.name !== "root_folder";
        },
    },
};
</script>
