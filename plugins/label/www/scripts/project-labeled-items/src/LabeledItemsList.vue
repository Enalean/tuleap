<template>
    <div class="labeled-items-list">
        <div v-if="loading" class="labeled-items-loading"></div>
        <div v-if="error" class="tlp-alert-danger labeled-items-error">{{ error }}</div>
        <div class="empty-pane-text" v-if="empty && ! error">{{ empty_message }}</div>
        <LabeledItem v-for="item in items"
                     v-bind:item="item"
                     v-bind:key="item.html_url"
        ></LabeledItem>
    </div>
</template>
(<script>
    import Gettext from 'node-gettext';
    import french_translations from '../po/fr.po';
    import LabeledItem from './LabeledItem.vue';
    import {getLabeledItems} from './rest-querier.js';

    const gettext_provider = new Gettext();
    gettext_provider.addTranslations('fr_FR', 'messages', french_translations);

    export default {
        name: 'LabeledItemsList',
        components: {LabeledItem},
        props: [
            'dataLabelsId',
            'dataProjectId',
            'dataLocale'
        ],
        data: function () {
            return {
                items: [],
                loading: true,
                error: false
            };
        },
        computed: {
            labels_id: function () {
                return JSON.parse(this.dataLabelsId);
            },
            empty: function () {
                return this.items.length === 0;
            },
            empty_message: function() {
                return gettext_provider.ngettext(
                    "There isn't any item corresponding to label",
                    "There isn't any item corresponding to labels",
                    this.labels_id.length
                );
            }
        },
        created: function() {
            gettext_provider.setLocale(this.dataLocale);
        },
        mounted: function() {
            this.loadLabeledItems();
        },
        methods: {
            loadLabeledItems: async function() {
                if (this.labels_id.length === 0) {
                    this.error   = gettext_provider.gettext("Please select one or more labels by editing this widget");
                    this.loading = false;
                    return;
                }

                try {
                    this.items = await getLabeledItems(
                        this.dataProjectId,
                        this.labels_id
                    );
                } catch (e) {
                    const {error} = await e.response.json();
                    this.error = error.code + ' ' + error.message;
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>)
