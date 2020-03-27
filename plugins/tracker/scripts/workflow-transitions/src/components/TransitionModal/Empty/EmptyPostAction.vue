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
    <div class="empty-pane">
        <div class="tracker-workflow-transition-modal-empty-action">
            <div v-translate>No action is currently defined.</div>
            <div v-translate>Start by adding one.</div>
        </div>
        <button
            class="tlp-button-primary tlp-button-outline"
            type="button"
            v-on:click="addNewPostAction()"
            data-test="add-post-action"
        >
            <i class="fa fa-plus tlp-button-icon"></i>
            <translate>Add action</translate>
        </button>
    </div>
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
