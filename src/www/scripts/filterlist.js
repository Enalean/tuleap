/*==================================================*
 
 Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 Modified by Nicolas Guerin

 Based on:

 * Picklist Script - Sean Geraty
 http://www.freewebs.com/sean_geraty/picklist.html

 * filterlist.js:
 Copyright 2003 Patrick Fitzgerald
 http://www.barelyfitz.com/webdesign/articles/filterlist/
 *==================================================*/
function filterlist(selectobj) {
    //==================================================
    // PARAMETERS
    //==================================================

    // HTML SELECT object
    // For example, set this to document.myform.myselect
    this.selectobj = selectobj;

    // Flags for regexp matching.
    // "i" = ignore case; "" = do not ignore case
    // You can use the set_ignore_case() method to set this
    this.flags = "i";

    // Which parts of the select list do you want to match?
    this.match_text = true;
    //this.match_value = false;

    // You can set the hook variable to a function that
    // is called whenever the select list is filtered.
    // For example:
    // myfilterlist.hook = function() { }

    // Flag for debug alerts
    // Set to true if you are having problems.
    this.show_debug = false;

    //==================================================
    // METHODS
    //==================================================

    //--------------------------------------------------
    this.init = function() {
        // This method initilizes the object.
        // This method is called automatically when you create the object.
        // You should call this again if you alter the selectobj parameter.

        if (!this.selectobj) return this.debug("selectobj not defined");
        if (!this.selectobj.options) return this.debug("selectobj.options not defined");

        // Make a copy of the select list options array
        this.optionscopy = new Array();
        if (this.selectobj && this.selectobj.options) {
            for (var i = 0; i < this.selectobj.options.length; i++) {
                // Create a new Option
                this.optionscopy[i] = new Option();

                // Set the text for the Option
                this.optionscopy[i].text = selectobj.options[i].text;

                // Set the value for the Option.
                // If the value wasn't set in the original select list,
                // then use the text.
                if (selectobj.options[i].value) {
                    this.optionscopy[i].value = selectobj.options[i].value;
                } else {
                    this.optionscopy[i].value = selectobj.options[i].text;
                }
            }
        }
    };

    //--------------------------------------------------
    this.reset = function() {
        // This method resets the select list to the original state.
        // It also unselects all of the options.

        this.set("");
    };

    //--------------------------------------------------
    // Remove option from the inner list (optionscopy)
    this.removeOption = function(option_value) {
        // Loop through the entire select list to find the corresponding option.
        // Note that if several options have the same value, only the first one will be removed.
        for (loop = 0; loop < this.optionscopy.length; loop++) {
            if (this.optionscopy[loop].value == option_value) {
                option_num = loop;
                break;
            }
        }
        //this.debug('Removing object#'+option_num+":"+this.optionscopy[option_num].text);
        // remove the matching item
        this.optionscopy[option_num] = null;
        // Then move all following objects one step
        for (loop = option_num; loop < this.optionscopy.length; loop++) {
            this.optionscopy[loop] = this.optionscopy[loop + 1];
        }
        this.optionscopy.length--; // useful?
    };

    //--------------------------------------------------
    // Add option to the inner list (optionscopy)
    this.addOption = function(new_option) {
        // insert the matching item
        // Should we put it at the right place??
        this.optionscopy[this.optionscopy.length] = new Option(
            new_option.text,
            new_option.value,
            false
        );
    };

    //--------------------------------------------------
    this.set = function(pattern) {
        // This method removes all of the options from the select list,
        // then adds only the options that match the pattern regexp.
        // It also unselects all of the options.

        //if (pattern.length ==1 ) return; // Don't filter if only one letter typed

        var loop = 0,
            index = 0,
            regexp,
            e;

        if (!this.selectobj) return this.debug("selectobj not defined");
        if (!this.selectobj.options) return this.debug("selectobj.options not defined");

        // Clear the select list so nothing is displayed
        this.selectobj.options.length = 0;

        // Set up the regular expression.
        // If there is an error in the regexp,
        // then return without selecting any items.
        try {
            // Initialize the regexp
            regexp = new RegExp(pattern, this.flags);
        } catch (e) {
            // There was an error creating the regexp.

            // If the user specified a function hook,
            // call it now, then return
            if (typeof this.hook == "function") {
                this.hook();
            }

            return;
        }

        // Loop through the entire select list and
        // add the matching items to the select list
        for (loop = 0; loop < this.optionscopy.length; loop++) {
            // This is the option that we're currently testing
            var option = this.optionscopy[loop];

            // Check if we have a match
            try {
                if (this.match_text && regexp.test(option.text)) {
                    // ||(this.match_value && regexp.test(option.value))) {

                    // We have a match, so add this option to the select list
                    // and increment the index
                    this.selectobj.options[index++] = new Option(option.text, option.value, false);
                }
            } catch (e) {
                return this.debug(
                    "object #" +
                        loop +
                        " not defined, previous obj=" +
                        this.selectobj.options[index - 2].text
                );
            }
        }

        if (index == 1) {
            this.selectobj.options[0].selected = true;
        }

        // If the user specified a function hook,
        // call it now
        if (typeof this.hook == "function") {
            this.hook();
        }
    };

    //--------------------------------------------------
    this.set_ignore_case = function(value) {
        // This method sets the regexp flags.
        // If value is true, sets the flags to "i".
        // If value is false, sets the flags to "".

        if (value) {
            this.flags = "i";
        } else {
            this.flags = "";
        }
    };

    //--------------------------------------------------
    this.debug = function(msg) {
        if (this.show_debug) {
            alert("FilterList: " + msg);
        }
    };

    //==================================================
    // Initialize the object
    //==================================================
    this.init();
}

// Two multi select box:
// Left: 'SelectList'
// Right: 'PickList'
// items may be moved from one to the other.

// Control flags for list selection and sort sequence
// Sequence is on option value (first 2 chars - can be stripped off in form processing)
// It is assumed that the select list is in sort sequence initially
var singleSelect = true; // Allows an item to be selected once only
var sortSelect = true; // Only effective if above flag set to true
var sortPick = true; // Will order the picklist in sort sequence
var noSelectText = "--- no selection ---";

// Initialise - invoked on load
function initIt() {
    var selectList = document.getElementById("SelectList");
    var selectOptions = selectList.options;
    var selectIndex = selectList.selectedIndex;
    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    //pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
    pickOptions[0] = new Option(noSelectText, -1); // Remove initial entry from picklist (was only used to set default width)
    if (!(selectIndex > -1)) {
        selectOptions[0].selected = true; // Set first selected on load
        selectOptions[0].defaultSelected = true; // In case of reset/reload
    }
    selectList.focus(); // Set focus on the selectlist
}

// Initialize with values in the Pick list
function addToPickListInit(option_value, option_text) {
    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    if (pickOptions[0].text == noSelectText) {
        // Remove the dummy option
        pickOptions[0] = null;
    }
    pickOptions[pickOptions.length] = new Option(option_text, option_value);
}

// Adds a selected item into the picklist
function addIt() {
    var selectList = document.getElementById("SelectList");
    var selectIndex = selectList.selectedIndex;
    var selectOptions = selectList.options;
    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;

    // Check if we should suppress the dummy option
    if (selectIndex > -1 && pickOLength == 1) {
        if (pickOptions[0].text == noSelectText) {
            // Remove the dummy option
            pickOptions[0] = null;
            pickOLength--;
        }
    }

    // An item must be selected
    while (selectIndex > -1) {
        pickOptions[pickOLength] = new Option(selectList[selectIndex].text);
        pickOptions[pickOLength].value = selectList[selectIndex].value;
        // If single selection, remove the item from the select list
        if (singleSelect) {
            myfilter.removeOption(selectList[selectIndex].value);
            selectOptions[selectIndex] = null;
        }
        if (sortPick) {
            var tempText;
            var tempValue;
            // Sort the pick list
            while (
                pickOLength > 0 &&
                pickOptions[pickOLength].text.toLowerCase() <
                    pickOptions[pickOLength - 1].text.toLowerCase()
            ) {
                tempText = pickOptions[pickOLength - 1].text;
                tempValue = pickOptions[pickOLength - 1].value;
                pickOptions[pickOLength - 1].text = pickOptions[pickOLength].text;
                pickOptions[pickOLength - 1].value = pickOptions[pickOLength].value;
                pickOptions[pickOLength].text = tempText;
                pickOptions[pickOLength].value = tempValue;
                pickOLength = pickOLength - 1;
            }
        }
        selectIndex = selectList.selectedIndex;
        pickOLength = pickOptions.length;
    }
    //selectOptions[0].selected = true;
}

// Deletes an item from the picklist
function delIt() {
    var selectList = document.getElementById("SelectList");
    var selectOptions = selectList.options;
    var selectOLength = selectOptions.length;
    var pickList = document.getElementById("PickList");
    var pickIndex = pickList.selectedIndex;
    var pickOptions = pickList.options;
    if (pickList[pickIndex].text == noSelectText) return;
    while (pickIndex > -1) {
        // If single selection, replace the item in the select list
        if (singleSelect) {
            selectOptions[selectOLength] = new Option(pickList[pickIndex].text);
            selectOptions[selectOLength].value = pickList[pickIndex].value;
            myfilter.addOption(selectOptions[selectOLength]);
        }
        pickOptions[pickIndex] = null;
        if (singleSelect && sortSelect) {
            var tempText;
            var tempValue;
            // Re-sort the select list
            while (
                selectOLength > 0 &&
                selectOptions[selectOLength].text.toLowerCase() <
                    selectOptions[selectOLength - 1].text.toLowerCase()
            ) {
                tempText = selectOptions[selectOLength - 1].text;
                tempValue = selectOptions[selectOLength - 1].value;
                selectOptions[selectOLength - 1].text = selectOptions[selectOLength].text;
                selectOptions[selectOLength - 1].value = selectOptions[selectOLength].value;
                selectOptions[selectOLength].text = tempText;
                selectOptions[selectOLength].value = tempValue;
                selectOLength = selectOLength - 1;
            }
        }
        pickIndex = pickList.selectedIndex;
        if (pickOptions.length == 0) {
            // insert dummy entry
            pickOptions[0] = new Option(noSelectText, -1);
        }
        selectOLength = selectOptions.length;
    }
}

// Selection - invoked on submit
function selIt(btn) {
    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;
    if (pickOLength < 1) {
        alert("No Selection made\nPlease Select using the [->] button");
        return false;
    }
    for (var i = 0; i < pickOLength; i++) {
        if (pickOptions[i].text != noSelectText) pickOptions[i].selected = true;
    }
    return true;
}
