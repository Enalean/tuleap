
document.observe('dom:loaded', function () {
    $$('.planning-artifact-chooser').each(function (select) {
        select.observe('change', function(evt) {
            select.form.submit();
        });
    });
    Planning.loadDroppables(document.body);
});