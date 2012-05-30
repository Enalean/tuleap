/**
 * Autocompleting project and user inputs within ProjectQuotaHtml::renderNewCustomQuotaForm() form
 */

var codendi = codendi || { };
document.observe('dom:loaded', function () {
    var prjAutocomplete  = new ProjectAutoCompleter('project', codendi.imgroot);
    var userAutocomplete = new UserAutoCompleter('requester', codendi.imgroot);
    prjAutocomplete.registerOnLoad();
    userAutocomplete.registerOnLoad();
});
