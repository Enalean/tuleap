/**
 * 
 */

var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };

codendi.tracker.defaultTemplates = '';

codendi.tracker.TemplateSelector = Class.create({
    /**
     * Constructor
     * 
     * @param Element form The form that holds the selector
     */
    initialize: function (form) {
        this.form = form;
        this.observeProjects();
        this.observerAutoComplete();
        this.cacheTemplates = { };
        if (codendi.tracker.defaultTemplates) {
            this.cacheTemplates[100] = codendi.tracker.defaultTemplates;
        }
    },
    observeProjects: function () {
        $('tracker_new_project_list').observe('change', this.selectOneProject.bindAsEventListener(this));
    },
    selectOneProject: function (evt) {
        // Ajax call
        var groupId = evt.target.value;
        if (!this.cacheTemplates[groupId]) {
            new Ajax.Updater($('tracker_list_trackers_from_project'), '/plugins/tracker/index.php?group_id='+groupId, {
                onLoading: function () {
                    /*var img = Builder.node('img', {
                        'src': "/themes/common/images/ic/spinner.gif",
                        'alt': 'Working...'});
                    var span_img = Builder.node('span', {id: 'search_indicator'});
                    span_img.appendChild(img);
                    $('tracker_list_trackers_from_project').appendChild(img);*/
                },
                onSuccess: function (response) {
                    /*$A(response.responseJSON).each(function (element) {
                        console.log(element.name+" "+element.id);
                    }*/
                    this.cacheTemplates[groupId] = response.responseText;
                }.bind(this)
            });
        } else {
            $('tracker_list_trackers_from_project').update(this.cacheTemplates[groupId]);
        }
        Event.stop(evt);
    },
    /**
     * Observe: press on enter or tab in the field (not other key because user might just navigate)
     * Observer: click on select result
     */
    observerAutoComplete: function () {
        $('tracker_new_prjname').observe('keypress', function (evt) {
            if (evt.keyCode == Event.KEY_RETURN || evt.keyCode == Event.KEY_TAB) {
                this.selectAutocompleter(evt);
            }
        }.bind(this));
        $('tracker_new_prjname').observe('update', this.selectAutocompleter.bindAsEventListener(this));
    },
    /**
     * Given an event, referesh the list of tracker templates
     */
    selectAutocompleter: function (evt) {
        this.updateTrackerTemplateList(evt.target.value);
    },
    /**
     * Refresh list of tracker templates given a project name
     */
    updateTrackerTemplateList: function (projectName) {
        $('tracker_new_other').selected = true;
        new Ajax.Updater($('tracker_list_trackers_from_project'), '/plugins/tracker/template_selector.php?func=plugin_tracker&target_name='+encodeURIComponent(projectName));
    }
});

// Crappy, cannot be added in dom:loaded because, by default, constructor wait for dom:loaded event..
var autocomplete = new ProjectAutoCompleter('tracker_new_prjname', codendi.imgroot, false);

document.observe('dom:loaded', function () {
    // Refresh project list
    var selector = new codendi.tracker.TemplateSelector($('tracker_create_new'));
    autocomplete.setAfterUpdateElement(function () {
        selector.updateTrackerTemplateList($F('tracker_new_prjname'));
    });
    
    /*var acc = new accordion('tracker_new_accordion', {
        classNames : {
            toggle: 'tracker_new_accordion_toggle',
            toggleActive: 'tracker_new_accordion_toggle_active',
            content: 'tracker_new_accordion_content'
        }
    });
    
    $$('.tracker_new_accordion_toggle').each(function(accordion) {
        $(accordion.next(0)).setStyle({
            height: '0px'
        });
    });

    acc.activate($$('#tracker_new_accordion .tracker_new_accordion_toggle')[0]);*/

});
