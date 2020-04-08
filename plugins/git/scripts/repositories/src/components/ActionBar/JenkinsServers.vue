<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
        <span
            class="tlp-badge-secondary tlp-badge-outline git-repository-list-header-badge"
            ref="jenkins_server_badge"
        >
            <translate
                v-bind:translate-n="countJenkinsServers"
                translate-plural="%{ countJenkinsServers } Jenkins servers setup"
            >
                %{ countJenkinsServers } Jenkins server setup
            </translate>
        </span>
        <section
            class="tlp-popover git-repository-list-header-popover"
            ref="jenkins_server_popover"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title" v-translate>Jenkins servers</h1>
            </div>
            <div class="tlp-popover-body">
                <p v-translate>
                    Theses servers are triggered at each push in any repository of the project:
                </p>
                <ul>
                    <li v-for="value in servers" v-bind:key="value.url">
                        <a v-bind:href="value.url">
                            <code>{{ value.url }}</code>
                        </a>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>

<script>
import { createPopover } from "tlp";

export default {
    name: "JenkinsServer",
    props: {
        servers: Array,
    },
    computed: {
        countJenkinsServers() {
            return this.servers.length;
        },
    },
    mounted() {
        createPopover(this.$refs.jenkins_server_badge, this.$refs.jenkins_server_popover, {
            placement: "right",
            trigger: "click",
        });
    },
};
</script>
