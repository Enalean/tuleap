/**
 * Autocompleting project and user inputs within ProjectQuotaHtml::renderNewCustomQuotaForm() form
 */

var tuleap = codendi || { };
document.observe('dom:loaded', function () {
    var prjAutocomplete  = new ProjectAutoCompleter('project', tuleap.imgroot);
    var userAutocomplete = new UserAutoCompleter('requester', tuleap.imgroot);
    prjAutocomplete.registerOnLoad();
    userAutocomplete.registerOnLoad();
});
