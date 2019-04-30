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
        <slot name="header"/>

        <statistics/>

        <div class="baseline-panes">
            <section class="tlp-pane baseline-content-filter-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="fa fa-list-ul tlp-pane-title-icon"></i>
                            <translate>Filters</translate>
                        </h1>
                    </div>

                    <section class="tlp-pane-section">
                        <slot name="filters"/>
                    </section>
                </div>
            </section>
            <section class="tlp-pane baseline-content-body-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            <i class="fa fa-files-o tlp-pane-title-icon"></i>
                            <translate>Content</translate>
                        </h1>
                    </div>
                    <section class="tlp-pane-section baseline-content">
                        <slot/>
                    </section>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import Statistics from "./Statistics.vue";

export default {
    name: "BaselineContentLayout",

    components: { Statistics }
};
</script>
