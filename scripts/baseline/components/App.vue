<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <div>
        <nav class="breadcrumb">
            <div class="breadcrumb-item">
                <router-link v-bind:to="{ name: 'IndexPage' }" class="breadcrumb-link">
                    <i class="fa fa-tlp-baseline breadcrumb-link-icon"></i>
                    <translate>
                        Baselines
                    </translate>
                </router-link>
            </div>
            <div v-if="!is_current_page_root" class="breadcrumb-item">
                <router-link to="" class="breadcrumb-link">
                    {{ current_page_title }}
                </router-link>
            </div>
        </nav>

        <main class="tlp-framed-vertically">
            <div class="tlp-framed-horizontally">
                <notification v-if="notification" v-bind:notification="notification" />
                <router-view v-bind:project_id="project_id" v-on:title="changeTitle" />
            </div>
        </main>

        <modal />
    </div>
</template>

<script>
import { mapState } from "vuex";
import Notification from "./Notification.vue";
import Modal from "./layout/Modal.vue";

export default {
    name: "App",
    components: { Modal, Notification },
    props: {
        project_id: {
            required: false,
            type: Number,
        },
    },
    data() {
        return {
            current_page_title: null,
        };
    },
    computed: {
        ...mapState("dialog_interface", ["notification"]),
        is_current_page_root() {
            return this.$route.name === "IndexPage";
        },
    },
    methods: {
        changeTitle(title) {
            this.current_page_title = title;
            document.title = `${title} - Tuleap`;
        },
    },
};
</script>
