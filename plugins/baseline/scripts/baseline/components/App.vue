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
    <div>
        <div class="breadcrumb-container">
            <breadcrumb-privacy
                v-bind:project_flags="project_flags"
                v-bind:privacy="privacy"
                v-bind:project_public_name="project_public_name"
            />
            <nav class="breadcrumb">
                <div class="breadcrumb-item breadcrumb-project">
                    <a v-bind:href="project_url" class="breadcrumb-link">
                        {{ project_icon }} {{ project_public_name }}
                    </a>
                </div>
                <div class="breadcrumb-item" v-bind:class="{ 'breadcrumb-switchable': is_admin }">
                    <router-link v-bind:to="{ name: 'IndexPage' }" class="breadcrumb-link">
                        <translate>Baselines</translate>
                    </router-link>
                    <div class="breadcrumb-switch-menu-container" v-if="is_admin">
                        <nav class="breadcrumb-switch-menu">
                            <span class="breadcrumb-dropdown-item">
                                <a class="breadcrumb-dropdown-link" v-bind:href="admin_url">
                                    <translate>Administration</translate>
                                </a>
                            </span>
                        </nav>
                    </div>
                </div>
                <div v-if="!is_current_page_root" class="breadcrumb-item">
                    <router-link to="" class="breadcrumb-link">
                        {{ current_page_title }}
                    </router-link>
                </div>
            </nav>
        </div>
        <h1 class="baseline-title-header" v-translate>Baselines</h1>

        <main class="tlp-framed-vertically">
            <div class="tlp-framed-horizontally">
                <notification-alert v-if="notification" v-bind:notification="notification" />
                <router-view v-bind:project_id="project_id" v-on:title="changeTitle" />
            </div>
        </main>

        <baseline-modal />
    </div>
</template>

<script>
import { mapState } from "vuex";
import NotificationAlert from "./NotificationAlert.vue";
import BaselineModal from "./layout/BaselineModal.vue";
import { BreadcrumbPrivacy } from "@tuleap/vue-breadcrumb-privacy";

export default {
    name: "App",
    components: { BaselineModal, NotificationAlert, BreadcrumbPrivacy },
    props: {
        project_id: {
            required: false,
            type: Number,
        },
        project_public_name: {
            required: true,
            type: String,
        },
        project_icon: {
            required: true,
            type: String,
        },
        project_url: {
            required: true,
            type: String,
        },
        privacy: {
            required: true,
            type: Object,
        },
        project_flags: {
            required: true,
            type: Array,
        },
        is_admin: {
            required: true,
            type: Boolean,
        },
        admin_url: {
            required: true,
            type: String,
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
