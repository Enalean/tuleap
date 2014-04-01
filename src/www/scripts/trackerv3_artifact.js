var tuleap                   = tuleap || {};
tuleap.trackersv3            = tuleap.trackersv3 || {};
tuleap.trackersv3.textarea     = tuleap.trackersv3.textarea || {};

tuleap.trackersv3.textarea.RTE = Class.create(codendi.RTE, {
    initialize: function ($super, element, options) {
        options = Object.extend({toolbar: 'tuleap'}, options || { });
        this.options = Object.extend({htmlFormat : false, id : 0}, options || { });
        $super(element, options);
        // This div contains comment format selection buttons
        var div = Builder.node('div');
        div.style.width = this.element.getWidth()+'px';
        var select_container = Builder.node('div', {'class' : 'rte_format'});
        select_container.appendChild(document.createTextNode("Format : "));
        div.appendChild(select_container);

        var div_clear = Builder.node('div', {'class' : 'rte_clear'});
        div.appendChild(div_clear)

        if (undefined == this.options.name) {
            this.options.name = 'comment_format'+this.options.id;
        }

        var selectbox = Builder.node('select', {'id' : 'rte_format_selectbox'+this.options.id, 'name' : this.options.name, 'class' : 'input-small'});
        select_container.appendChild(selectbox);

        // Add an option that tells that the content format is text
        // The value is defined in Artifact class.
        var text_option = Builder.node(
            'option',
            {'value' : '0', 'id' : 'comment_format_text'+this.options.id},
            "Text"
        );
        selectbox.appendChild(text_option);

        // Add an option that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_option = Builder.node(
            'option',
            {'value': '1', 'id' : 'comment_format_html'+this.options.id},
            "HTML"
        );
        selectbox.appendChild(html_option);

        Element.insert(this.element, {before: div});

        div.appendChild(this.element);

        if (options.htmlFormat == true) {
            selectbox.selectedIndex = 1;
        } else {
            selectbox.selectedIndex = 0;
        }

        if ($('comment_format_html'+this.options.id).selected == true) {
            this.init_rte();
        }

        if (this.options.toggle) {
           selectbox.observe('change', this.toggle.bindAsEventListener(this, selectbox));
        }
    },

    toggle: function ($super, event, selectbox) {
        var option = selectbox.options[selectbox.selectedIndex].value ? 'html' : 'text',
            id     = this.element.id;

            if ($(id).hasAttribute("data-required") && option == 'text' && this.rte) {
                $(id).removeAttribute("data-required");
                $(id).writeAttribute("required", true);
            };

        $super(event, option);
    },

    init_rte : function($super) {
        var id = this.element.id;

        $super();
        (function recordRequiredAttribute() {
            if ($(id).hasAttribute("required")) {
                $(id).removeAttribute("required");
                $(id).writeAttribute("data-required", true);
            }
        })();
    }
});

document.observe('dom:loaded', function () {
    var newFollowup = $('tracker_artifact_comment');
    if (newFollowup) {
        new tuleap.trackersv3.textarea.RTE(newFollowup, {toggle: true, default_in_html: false, id : ''});
    }
    /*var massChangeFollowup = $('artifact_masschange_followup_comment');
    if (massChangeFollowup) {
        new tuleap.trackers.textarea.RTE(massChangeFollowup, {toggle: true, default_in_html: false, id: 'mass_change'});
    }*/
});
