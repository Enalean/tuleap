/* global Class:readonly Prototype:readonly $:readonly Builder:readonly Ajax:readonly */
/*
 * Abstract class to manage autocompletion, see after for concret classes
 */
var AutoCompleter = Class.create({
    /**
     * Constructor
     */
    initialize: function (elementId, imgPath, multiple, options) {
        this.elementId = elementId;
        this.options = Object.extend(
            {
                autoLoad: true,
                imgPath: imgPath,
                multiple: multiple,
            },
            options || {},
        );
        // The url to call to get completion list
        this.url = "";
        this.afterUpdateElement = Prototype.emptyFunction;

        if (this.options.autoLoad) {
            this.registerOnLoadEvent = this.registerOnLoad.bindAsEventListener(this);
            document.observe("dom:loaded", this.registerOnLoadEvent);
        }
    },
    /**
     * Attach a listen to the given element (input text) to perfom
     *   autocompletion.
     */
    registerOnLoad: function () {
        this.element = $(this.elementId);

        if (this.element === null) {
            return;
        }

        if (this.options["allowNull"]) {
            this.element.observe("click", function () {
                this.stopObserving("blur");
            });
        }

        var tokens = "";
        if (this.options.multiple == true) {
            tokens = [",", ";"];
        }

        if (this.element) {
            // Spinner
            var img = Builder.node("img", {
                src: this.options.imgPath + "/ic/spinner.gif",
                alt: "Working...",
            });
            var span_img = Builder.node("span", { id: "search_indicator" });
            span_img.appendChild(img);
            Element.hide(span_img);
            if (!$(this.options.spinnerParent)) {
                this.element.parentNode.appendChild(span_img);
            } else {
                $(this.options.spinnerParent).insert({ after: span_img });
            }

            // List div
            var update = Builder.node("div", {
                id: "search_choices",
                class: "searchAsYouType",
            });
            Element.hide(update);

            // Insert the div at the bottom of the document because the old way
            // this.element.parentNode was not working in some cases with
            // IE6. This case happens in cc fields in trackers (probably
            // related to the deep of the tree).
            document.body.appendChild(update);

            // Autocomplete
            new Ajax.Autocompleter(this.element, update, this.url, {
                tokens: tokens,
                minChars: "3",
                paramName: "name",
                indicator: "search_indicator",
                afterUpdateElement: this.afterUpdateElement,
            });
        }
    },
    /**
     * Set function executed after autocompletion update
     */
    setAfterUpdateElement: function (callback) {
        this.afterUpdateElement = callback;
    },
});

/**
 * Usage:
 * new UserAutoCompleter('form_unix_name', '".util_get_dir_image_theme()."', false)

 */
window.UserAutoCompleter = Class.create(AutoCompleter, {
    /**
     * Constructor
     */
    initialize: function ($super, elementId, imgPath, multiple, options) {
        $super(elementId, imgPath, multiple, options);
        this.url = "/user/autocomplete.php";
        if (this.options.codendiUsersOnly == 1) {
            this.url += "?codendi_user_only=1";
        }
    },
});

/**
 * Usage:
 * new ProjectAutoCompleter('form_unix_name', '".util_get_dir_image_theme()."', false)

 */
window.ProjectAutoCompleter = Class.create(AutoCompleter, {
    /**
     * Constructor
     */
    initialize: function ($super, elementId, imgPath, multiple, options) {
        $super(elementId, imgPath, multiple, options);
        this.url = "/project/autocomplete.php";
        if (this.options.private == 1) {
            this.url += "?private=1";
        }
    },
});
