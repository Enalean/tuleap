/**
 * Usage:
 * new UserAutoCompleter('form_unix_name', '".util_get_dir_image_theme()."', false)
 
 */
var UserAutoCompleter = Class.create({
    /**
     * Constructor
     */
    initialize: function(elementId, imgPath, multiple, options) {
        this.elementId = elementId;
        if(!options) {
            this.options = new Array();
        } else {
            this.options = options;
        }
        this.options['imgPath']  = imgPath;
        this.options['multiple'] = multiple;

        this.registerOnLoadEvent = this.registerOnLoad.bindAsEventListener(this);
        document.observe('dom:loaded', this.registerOnLoadEvent);
    },
    /**
     * Attach a listen to the given element (input text) to perfom
     *   autocompletion.
     */
    registerOnLoad: function () {
        this.element = $(this.elementId);
        
        var url = '/user/autocomplete.php';
        if(this.options.codendiUsersOnly == 1) {
            url += '?codendi_user_only=1';
        }
        var tokens = '';
        if(this.options.multiple == true) {
            tokens = [',', ';'];
        }

        if (this.element) {
            // Spinner
            var img = Builder.node('img', {
                'src': this.options.imgPath+"/ic/spinner.gif",
                'alt': 'Working...'});
            var span_img = Builder.node('span', {id: 'user_search_indicator'});
            span_img.appendChild(img);
            Element.hide(span_img);
            if(!$(this.options.spinnerParent)) {
                this.element.parentNode.appendChild(span_img);
            } else {
                $(this.options.spinnerParent).insert({'after': span_img});
            }

            // List div
            var update = Builder.node('div', {
                'id':    'user_search_choices',
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
                'paramName': 'user_name',
                'indicator': 'user_search_indicator'
            });
        }
    }
});
