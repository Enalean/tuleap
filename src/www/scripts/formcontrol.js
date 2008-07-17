function checkAll(check) {

    for(i=0;i<document.getElementsByTagName('input').length;i++) {
	
	if (document.getElementsByTagName('input')[i].type == 'checkbox') {
	    
	    if (check == 1) {
		document.getElementsByTagName('input')[i].checked = true;
	    }
	    if (check == 0) {
		document.getElementsByTagName('input')[i].checked = false;
	    }
	}
    }
}

function initField(val) {
    document.getElementById('nbemail').value = val;
}