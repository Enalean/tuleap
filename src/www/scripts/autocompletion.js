function autocomplete() {
    
new Ajax.Autocompleter ('username',
                        'username_update',
			'UserAutocompletionForm.class.php',
                        {
                            method: 'post',
			    minChars: 1,
                            paramName: 'user_name_search'
			}
		       );


}
