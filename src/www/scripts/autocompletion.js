function autocomplete() {
    
/*new Ajax.Autocompleter ('username',
                        'username_update',
			'UserAutocompletionForm.class.php',
                        {
                            method: 'post',
			    minChars: 1,
                            paramName: 'user_name_search'
			}
		       );*/


 Event.observe(window, 'load', function () {
                            var ori = $('gen_prop_allowed_project');
                            if (ori) {
                                var update = Builder.node('div', {id:'gen_prop_allowed_project_choices', style:'background:white'});
                                Element.hide(update);
                                ori.parentNode.appendChild(update);
                                new Ajax.Autocompleter('gen_prop_allowed_project', update, '?view=ajax_projects', {
                                        tokens: ','
                                });
                            }
                    });


}
