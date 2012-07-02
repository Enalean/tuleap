
document.observe('dom:loaded', function () {
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