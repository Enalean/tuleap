<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <section class="empty-state-page">
        <p class="empty-state-text">
            {{ $gettext("No action is currently defined. Start by adding one.") }}
        </p>
        <button
            class="empty-state-action tlp-button-primary tlp-button-outline"
            type="button"
            v-on:click="addNewPostAction()"
            data-test="add-post-action"
        >
            <i class="fa fa-plus tlp-button-icon"></i>
            <span>{{ $gettext("Add action") }}</span>
        </button>
    </section>
</template>
<script>
import { mapMutations } from "vuex";

export default {
    name: "EmptyPostAction",
    methods: {
        ...mapMutations({
            addNewPostAction: "transitionModal/addPostAction",
        }),
    },
};
</script>
