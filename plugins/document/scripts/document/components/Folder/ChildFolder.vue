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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div class="tlp-framed" v-if="! does_folder_have_any_error">
        <folder-view/>
    </div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";
import FolderView from "./FolderView.vue";

export default {
    name: "ChildFolder",
    components: { FolderView },
    computed: {
        ...mapGetters(["does_folder_have_any_error"])
    },
    watch: {
        $route(to) {
            this.loadFolder(parseInt(to.params.item_id, 10));
        }
    },
    mounted() {
        this.loadFolder(parseInt(this.$route.params.item_id, 10));
    },
    methods: {
        ...mapActions(["loadFolder"])
    }
};
</script>
