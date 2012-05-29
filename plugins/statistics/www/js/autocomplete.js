/**
 * 
 */

var codendi = codendi || { };

document.observe('dom:loaded', function () {
    var prjAutocomplete  = new ProjectAutoCompleter('project', '/themes/Tuleap/images/');
    var userAutocomplete = new UserAutoCompleter('requester', '/themes/Tuleap/images/');
    prjAutocomplete.registerOnLoad();
    userAutocomplete.registerOnLoad();
});
