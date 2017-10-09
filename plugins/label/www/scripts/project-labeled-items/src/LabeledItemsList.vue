(<template>
    <div class="labeled-items-list">
        <div v-if="loading" class="labeled-items-loading"></div>
        <div v-if="error" class="tlp-alert-danger labeled-items-error">{{ error }}</div>
        <div class="empty-pane-text" v-if="empty && ! loading && ! error">{{ empty_message }}</div>
        <LabeledItem v-for="item in items"
                     v-bind:item="item"
                     v-bind:key="item.html_url"
        ></LabeledItem>
        <div class="labeled-items-list-more" v-if="has_more_items">
            <button class="tlp-button-primary tlp-button-outline" v-on:click="loadMore">
                <i class="tlp-button-icon fa fa-spinner fa-spin" v-if="is_loading_more"></i>
                {{ load_more }}
            </button>
        </div>
    </div>
</template>)
(<script>
    import Gettext from 'node-gettext';
    import french_translations from '../po/fr.po';
    import LabeledItem       from './LabeledItem.vue';
    import {getLabeledItems} from './rest-querier.js';

    const gettext_provider = new Gettext();
    gettext_provider.addTranslations('fr_FR', 'messages', french_translations);

    export default {
        name: 'LabeledItemsList',
        components: {LabeledItem},
        props: [
            'dataLabelsId',
            'dataProjectId',
            'dataLocale',
        ],
        data: function () {
            return {
                items: [],
                loading: true,
                error: false,
                are_there_items_user_cannot_see: false,
                offset: 0,
                limit: 50,
                has_more_items: false,
                is_loading_more: false
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
                if (this.are_there_items_user_cannot_see) {
                    return gettext_provider.gettext("There are no items you can see");
                }
                return gettext_provider.ngettext(
                    "There isn't any item corresponding to label",
                    "There isn't any item corresponding to labels",
                    this.labels_id.length
                );
            },
            load_more: function () {
                return gettext_provider.gettext("Load more");
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
                    const {
                        labeled_items,
                        are_there_items_user_cannot_see,
                        has_more,
                        offset
                    } = await getLabeledItems(
                        this.dataProjectId,
                        this.labels_id,
                        this.offset,
                        this.limit
                    );

                    this.offset         = offset;
                    this.has_more_items = has_more;
                    this.items          = this.items.concat(labeled_items);

                    this.are_there_items_user_cannot_see = are_there_items_user_cannot_see;
                } catch (e) {
                    const {error} = await e.response.json();
                    this.error    = error.code + ' ' + error.message;
                } finally {
                    this.loading = false;
                }
            },
            loadMore: async function () {
                this.is_loading_more = true;

                this.offset += this.limit;
                await this.loadLabeledItems();

                this.is_loading_more = false;
            }
        }
    };
</script>)
