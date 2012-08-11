
document.observe('dom:loaded', function () {

    function ensureThatIdsAreUniqOnBothPanelsOfThePlanningWithAHack() {
        $$('.milestone-content .dropdown').each(function (dropdown) {
            dropdown.id += '-backlog';
            var a = dropdown.down('a.dropdown-toggle');
            if (a) {
                a.writeAttribute('data-target', a.readAttribute('data-target') + '-backlog');
            }
        });
    }
    ensureThatIdsAreUniqOnBothPanelsOfThePlanningWithAHack();

    $$('.planning-artifact-chooser').each(function (select) {
        select.observe('change', function(evt) {
            select.form.submit();
        });
    });

    Ajax.Responders.register({
        onCreate: Planning.toggleFeedback,
        onComplete: Planning.toggleFeedback
    });
    Planning.loadSortables(document.body);
});
