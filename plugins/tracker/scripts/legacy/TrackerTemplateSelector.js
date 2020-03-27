/**
 *
 */

/* global Class:readonly $:readonly Ajax:readonly $F:readonly ProjectAutoCompleter:readonly */

var codendi = codendi || {};
codendi.tracker = codendi.tracker || {};

// Store default templates in plain HTML because we cannot query
// templates from project n°100 through AJAX (damn URLVerification)
codendi.tracker.defaultTemplates = "";

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
        this.cacheTemplates = {};
        if (codendi.tracker.defaultTemplates) {
            this.cacheTemplates[100] = codendi.tracker.defaultTemplates;
        }
    },
    observeProjects: function () {
        $("tracker_new_project_list").observe(
            "change",
            this.selectOneProject.bindAsEventListener(this)
        );
    },
    selectOneProject: function (evt) {
        var groupId = evt.target.value;
        this.loadTemplateList(groupId);
        Event.stop(evt);
    },
    /**
     * Observe: press on enter or tab in the field (not other key because user might just navigate)
     * Observer: click on select result
     */
    observerAutoComplete: function () {
        $("tracker_new_prjname").observe(
            "keypress",
            function (evt) {
                if (evt.keyCode == Event.KEY_RETURN || evt.keyCode == Event.KEY_TAB) {
                    this.selectAutocompleter(evt);
                }
            }.bind(this)
        );
        $("tracker_new_prjname").observe(
            "update",
            this.selectAutocompleter.bindAsEventListener(this)
        );
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
        var m = projectName.match(/\(([^()]+)\)$/);
        if (m && m[1]) {
            projectName = m[1];
        }
        new Ajax.Request("/projects/" + encodeURIComponent(projectName), {
            onSuccess: function (transport) {
                var groupId = transport.responseJSON.id;
                var existing_options = $("tracker_new_project_list").select(
                    "option[value=" + groupId + "]"
                );
                if (existing_options[0]) {
                    existing_options[0].selected = true;
                } else {
                    var opt = new Element("option", {
                        value: groupId,
                        selected: true,
                    }).update(transport.responseJSON.name);
                    $("tracker_new_other").show().insert(opt);
                }
                this.loadTemplateList(groupId);
            }.bind(this),
        });
    },
    /**
     * load (ajax) templates for a given project
     */
    loadTemplateList: function (groupId) {
        if (groupId >= 100 || groupId == 1) {
            if (!this.cacheTemplates[groupId]) {
                new Ajax.Request("/plugins/tracker/index.php?group_id=" + groupId, {
                    onSuccess: function (response) {
                        this.cacheTemplates[groupId] = response.responseText;
                    }.bind(this),
                    onFailure: function () {
                        this.cacheTemplates[groupId] =
                            "<option>" +
                            codendi.getText("tracker_template", "no_template") +
                            "</option>";
                    }.bind(this),
                    onComplete: function () {
                        $("tracker_list_trackers_from_project").update(
                            this.cacheTemplates[groupId]
                        );
                    }.bind(this),
                });
            } else {
                $("tracker_list_trackers_from_project").update(this.cacheTemplates[groupId]);
            }
        }
    },
});

document.observe("dom:loaded", function () {
    if (!$("tracker_create_new")) {
        return;
    }

    // Refresh project list
    const selector = new codendi.tracker.TemplateSelector($("tracker_create_new"));
    const autocomplete = new ProjectAutoCompleter("tracker_new_prjname", codendi.imgroot, false, {
        autoLoad: false,
    });
    if (autocomplete) {
        autocomplete.setAfterUpdateElement(function () {
            selector.updateTrackerTemplateList($F("tracker_new_prjname"));
        });
        autocomplete.registerOnLoad();
    }
});
