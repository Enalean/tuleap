function autochangeStatus(form) {

    var codexstatus = form.form_codexstatus;
    var unixstatus = form.form_unixstatus;
    
    if (codexstatus.value == 'S') {

	for (i=0;i<unixstatus.length;i++) {

	    if (unixstatus.options[i].value == 'N') {
		unixstatus.options[i].selected = true;
	    }
	}
    }

    if(codexstatus.value == 'D') {

	for (i=0;i<unixstatus.length;i++) {

	    if (unixstatus.options[i].value == 'D') {
		unixstatus.options[i].selected = true;
	    }
	}
    }
}
