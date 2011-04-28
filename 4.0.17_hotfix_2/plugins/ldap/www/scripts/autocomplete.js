/**
 * Usage (from a PHP code, see ldapPlugin::project_admin_add_user_form):
 * new LdapGroupAutoCompleter('project_admin_add_ldap_group',
 *                            '".$this->getPluginPath()."',
 *                            '".util_get_dir_image_theme()."',
 *                            'project_admin_add_ldap_group',
 *                            false);
 */
var LdapGroupAutoCompleter = Class.create({
    /**
     * Constructor
     */
    initialize: function(elementId, pluginPath, imgPath, spinnerParent, multiple) {
        this.elementId = elementId;
        this.options = Array();
        this.options['pluginPath']    = pluginPath;
        this.options['imgPath']       = imgPath;
        this.options['spinnerParent'] = spinnerParent;
        this.options['multiple']      = multiple;

        this.registerOnLoadEvent = this.registerOnLoad.bindAsEventListener(this);
        document.observe('dom:loaded', this.registerOnLoadEvent);
    },
    dispose: function() {
        document.stopObserving('dom:loaded', this.registerOnLoadEvent);
    },
    /**
     * Attach a listen to the given element (input text) to perfom
     *   autocompletion.
     */
    registerOnLoad: function () {
        this.element = $(this.elementId);
        var url = this.options.pluginPath+'/autocomplete.php';

        var tokens = '';
        if(this.options.multiple == true) {
            tokens = [',', ';'];
        }

        if (this.element) {
            // Spinner
            var img = Builder.node('img', {
                'src': this.options.imgPath+"/ic/spinner.gif",
                'alt': 'Working...'});
            var span_img = Builder.node('span', {id: 'ldap_group_search_indicator'});
            span_img.appendChild(img);
            Element.hide(span_img);
            if(!$(this.options.spinnerParent)) {
                this.element.parentNode.appendChild(span_img);
            } else {
                $(this.options.spinnerParent).insert({'after': span_img});
            }

            // List div
            update = Builder.node('div',{
                'id':'ldap_group_choices',
                'class': 'searchAsYouType'});
            Element.hide(update);

            // Insert the div at the bottom of the document because the old way
            // this.element.parentNode was not working in some cases with
            // IE6. This case happens in cc fields in trackers (probably
            // related to the deep of the tree).
            document.body.appendChild(update);

            // Autocomplete
            new Ajax.Autocompleter(this.element, update, url, {
                'tokens': tokens,
                'minChars': '3',
                'paramName': 'ldap_group_name',
                'indicator': 'ldap_group_search_indicator'
            });
        }
    }
});