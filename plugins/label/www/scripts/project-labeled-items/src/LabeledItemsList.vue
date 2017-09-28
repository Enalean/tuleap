<template>
    <div class="labeled-items-list">
        <div v-if="loading" class="labeled-items-loading"></div>
        <div v-if="error" class="tlp-alert-danger labeled-items-error">{{ error }}</div>
        <div v-if="empty">
            <div class="empty-pane-text labeled-items-empty">
                No item corresponds to the selected label(s)
            </div>
        </div>
        <LabeledItem v-for="item in items"
                     v-bind:item="item"
                     v-bind:key="item.html_url"
        ></LabeledItem>
    </div>
</template>
<script>
    import LabeledItem from './LabeledItem.vue';
    import {getLabeledItems} from './rest-querier.js';

    export default {
        name: 'LabeledItemsList',
        components: {LabeledItem},
        props: ['dataLabelsId', 'dataProjectId'],
        data: function () {
            return {
                items: [],
                loading: true,
                error: false
            };
        },
        computed: {
            empty: function () {
                return this.items.length === 0;
            }
        },
        mounted: function() {
            this.loadLabeledItems();
        },
        methods: {
            loadLabeledItems: async function() {
                const labels_id = JSON.parse(this.dataLabelsId);
                if (labels_id.length === 0) {
                    this.error   = 'Please select one or more labels by editing this widget';
                    this.loading = false;
                    return;
                }

                try {
                    const {labeled_items} = await getLabeledItems(
                        this.dataProjectId,
                        labels_id
                    );
                    this.items = labeled_items;
                } catch (e) {
                    const {error} = await e.response.json();
                    this.error = error.code + ' ' + error.message;
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
