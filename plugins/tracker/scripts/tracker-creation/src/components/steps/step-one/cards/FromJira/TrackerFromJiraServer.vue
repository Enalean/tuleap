<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
        <div class="tlp-form-element">
            <label class="tlp-label" for="jira_url" v-translate>Your Jira server url</label>
            <input
                type="url"
                id="jira_url"
                placeholder="https://jira_server.com"
                v-model="value.server_url"
                pattern="https?://.+"
                class="tlp-input"
            />
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="username" v-translate>User email</label>
            <input
                type="text"
                id="username"
                placeholder="Bob@example.com"
                v-model="value.user_email"
                class="tlp-input"
            />
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="token" v-translate>Token</label>
            <input
                type="text"
                id="token"
                placeholder="ToKeNGLsLC1wY4j6qiuk61kFX"
                v-model="value.token"
                class="tlp-input"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Credentials } from "../../../../../store/type";
import TrackerFromJiraProject from "./TrackerFromJiraProject.vue";

@Component({
    components: { TrackerFromJiraProject },
})
export default class TrackerFromJiraServer extends Vue {
    @Prop({ required: true })
    readonly value!: Credentials[];
}
</script>
