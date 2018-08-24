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
    <div>
        <create-pullrequest-button />
    </div>
</template>

<script>
import store from "../store/index.js";
import CreatePullrequestButton from "./CreatePullrequestButton.vue";

export default {
    name: "App",
    store,
    components: {
        CreatePullrequestButton
    },
    props: {
        repository_id: Number,
        parent_repository_id: Number
    },
    mounted() {
        this.$store.dispatch("init", {
            repository_id: this.repository_id,
            parent_repository_id: this.parent_repository_id
        });
    }
};
</script>
