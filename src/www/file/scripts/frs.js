function replace(expr,a,b) {
	var i=0
	while (i!=-1) {
		i=expr.indexOf(a,i);
		if (i>=0) {
			expr=expr.substring(0,i)+b+expr.substring(i+a.length);	    
			i+=b.length;
		}
	}
	return expr
}

function update_news() {
	var rel_name = $('release_name');
	var subject = $('release_news_subject');      
	var details = $('release_news_details');

	var a = this.relname;
	var b = rel_name.value;
	var expr1 = subject.value;
	var expr2 = details.value;
    
	new_subject = replace(expr1,a,b);
	new_details = replace(expr2,a,b);
	subject.value = new_subject;
	details.value = new_details;
}

//variables
var nb_rows = 1;
var nb_files = 0;
var used_ftp_files = [];
var available_ftp_files =[];
		
//function to add a new file by http or ftp/scp mode
function add_new_file() {
	nb_files ++;
	var id = nb_rows++;
	var builder_node_files = [];
	var builder_node_processor = [];
	var builder_node_type = [];
	var non_used_ftp_files = available_ftp_files;
			
	//remove all ftp files aldready selected in the avalaible ftp file list (result in the non_used_ftp_files)
	used_ftp_files.each(function(num){
		non_used_ftp_files = non_used_ftp_files.without(num);
	});
			
	//for each non used ftp files, add a corresponding option ligne (used in the select files)			
	non_used_ftp_files.each(function(num){
		builder_node_files.push(Builder.node('option', {value:num}, num));
	});
			
	//TR tag construction		 	
	var row = Builder.node('tr', {id:'row_'+id});
			
	//TD tag constuction, add the trash image this tag (used to remove the line)
	var cell_trash = Builder.node('td');
	var image = Builder.node('img', {src:'./../images/delete.png', onclick:'delete_file(\'row_'+id+'\','+id+')', style:'cursor:pointer'});
			
	row.appendChild(cell_trash);
			
	//TD tag constuction, add the select file boxe to this tag (used to choose the file)
	var cell = Builder.node('td', {id:'td_file_'+id});
	var select = Builder.node('select', {name:'ftp_file_list'}, 
		[Builder.node('option', {value:'-1'}, choose),
		Builder.node('optgroup', {label:local_file}, 
		[Builder.node('option', {value:'-2'}, browse)]),
		Builder.node('optgroup', {label:scp_ftp_files}, 
		builder_node_files)
		]);
	select.options[0].selected = 'selected';
			
	//add the onchange event on the select boxe
	Event.observe(select, 'change', (function () {
		if (this.options[this.selectedIndex].value == '-2') {
			//the http mode was selected
			Element.show('file_'+id);
			//Element.hide(this);
			Element.remove(this);
			Element.remove('ftp_file_'+id);
			$('processor_'+id).name='file_processor[]';
			$('type_'+id).name='file_type[]';
			cell_trash.appendChild(image);
		}else if(this.options[this.selectedIndex].value != '-1'){
			//the ftp/scp move was selected, wa change the select box to a readonly text field
			//we add the file to the used_ftp_files list
			//Element.hide(this);
			Element.remove(this);
			Element.remove('file_'+id);
			Element.show('ftp_file_'+id);
			$('ftp_file_'+id).value=this.options[this.selectedIndex].value;
			used_ftp_files.push(this.options[this.selectedIndex].value);
			$('processor_'+id).name='ftp_file_processor[]';
			$('type_'+id).name='ftp_file_type[]';
			cell_trash.appendChild(image);
			$('td_file_'+id).innerHTML += this.options[this.selectedIndex].value;
					
		}
	}).bindAsEventListener(select), true);
	cell.appendChild(select);

	//Browse file field creation
	var file = Builder.node('input', {'type':'file', id:'file_'+id, name:'file[]'});
	Element.hide(file);
	cell.appendChild(file);
		
	//ftp file field creation
	var ftp_file = Builder.node('input', {'type':'hidden', id:'ftp_file_'+id, name:'ftp_file[]'});
	Element.hide(ftp_file);
	cell.appendChild(ftp_file);

	row.appendChild(cell);
			
			
	//for each processor, add a corresponding option ligne 			
	builder_node_processor.push(Builder.node('option', {value:'100'}, choose));
	processor_id.each(function(id, item){
	 		builder_node_processor.push(Builder.node('option', {value:id}, processor_name[item]));
		 	});
			
	//TD tag constuction, add the select processor type boxe to this tag (used to choose the processor)
	cell = Builder.node('td');
	var select = Builder.node('select', {id:'processor_'+id}, builder_node_processor);
	select.options[0].selected = 'selected';
	cell.appendChild(select);
	row.appendChild(cell);
	
	//for each type, add a corresponding option ligne 			
	builder_node_type.push(Builder.node('option', {value:'100'}, choose));
	type_id.each(function(id, item){
		 		builder_node_type.push(Builder.node('option', {value:id}, type_name[item]));
			 	});
			
	//TD tag constuction, add the select file type boxe to this tag (used to choose the type)
	cell = Builder.node('td');
	var select = Builder.node('select', {id:'type_'+id}, builder_node_type);
	select.options[0].selected = 'selected';
	cell.appendChild(select);
	row.appendChild(cell);

	$('files_body').appendChild(row);
}
		
function delete_file(row_id, id){
	nb_files --;
	var file = $('file_'+id);
	if($('file_'+id)==null){
		// we remove the file from the used ftp files list
		used_ftp_files = used_ftp_files.without($('ftp_file_'+id).value);
	}
	Element.remove(row_id);
	if((release_mode == 'creation' && nb_files==0) || (release_mode == 'edition' && $('nb_files').value==0 && nb_files==0)){
		add_new_file();
	}
}
		
function add_change_log(){
	Element.hide('add_change_log');
	Element.show('change_log_title');
	Element.show('change_log_area');
	new Insertion.After('change_log', '<a id="cl_upload_link" href="#upload_change_log" onclick="Element.show( \'upload_change_log\'); Element.hide( \'cl_upload_link\'); return false;">'+upload_text+'</a>');
}
		
function view_change_permissions(){
	Element.hide('default_permissions');
	Element.show('permissions'); 
}
		
Event.observe(window, 'load', function() {
	//Add new file part
	//Element.hide('row_0');
	Element.remove('row_0');
	if(release_mode == 'creation' || (release_mode == 'edition' && $('nb_files').value==0)){
		add_new_file();
	}
	new Insertion.After('files', '<a id="file_help_link" href="#help" onclick="Element.hide(\'file_help_link\');Element.show( \'files_help\'); return false;"> [?]</a>');
	new Insertion.After('files', '<a href="#add_new_file" onclick="add_new_file(); return false;">'+add_file_text+'<a>');
	
	//Upload files help
	Element.hide('files_help');
			
	//Release Notes
	Element.hide('upload_release_notes');
	new Insertion.After('release_notes', '<a id="rn_upload_link" href="#upload_release_notes" onclick="Element.show( \'upload_release_notes\'); Element.hide( \'rn_upload_link\');return false;">'+upload_text+'</a>');
		
	//Change Log
	if((release_mode == 'edition' && $('text_area_change_log').value=='') || release_mode == 'creation'){
		Element.hide('change_log_title');
		Element.hide('upload_change_log');
		Element.hide('change_log_area');
		new Insertion.Before('change_log_title', '<TR id="add_change_log"><TD><a href="#add_change_log" onclick="add_change_log(); return false;">'+add_change_log_text+'</a></TD></TR>');
	}		
	//News
	Element.hide('tr_subject');
	Element.hide('tr_details');
	Element.hide('tr_public');
	Element.hide('tr_private');
			
	if($('submit_news')!=null){
		Event.observe($('submit_news'), 'click', function(){
			if($('submit_news').checked){
				Element.show('tr_subject');
				Element.show('tr_details');
				Element.show('tr_public');
				Element.show('tr_private');
			}else{
				Element.hide('tr_subject');
				Element.hide('tr_details');
				Element.hide('tr_public');
				Element.hide('tr_private');
			}
		});
	}
			
	//Permissions
	if($('package_id')!=null){
		Event.observe($('package_id'), 'change', function(){
			if(release_mode == 'creation'){
				new Ajax.Updater('permissions_list', 'frsajax.php?group_id='+group_id +'&action=permissions_frs_package&package_id=' + $('package_id').value,{ method:'get' });
			}		
				
			
		});
	}
	Element.hide('permissions');
	if(release_mode == 'edition'){default_permissions_text += '<B>'+ ugroups_name+'</B>';}
	new Insertion.Before('permissions', '<TR id="default_permissions">'+
											'<TD>'+default_permissions_text+
												'<a href="#change_permissions" onclick="view_change_permissions(); return false;">'+view_change_text+'</a></TD></TR>');

		
		Event.observe($('frs_form'), 'submit', function(evt){
			$('feedback').innerHTML = '';
			var valide = false;
			if(release_mode == 'creation'){
				if( $('package_id')){
					var package_id = $('package_id').value;
				}else { var package_id = null; }
				var url = 'frsajax.php?group_id='+group_id +'&action=validator_frs_create&package_id=' + package_id+'&date=' + $('release_date').value+
								'&name=' + $('release_name').value;
			}else{
				var url = 'frsajax.php?group_id='+group_id +'&action=validator_frs_update&package_id=' + $('new_package_id').value+'&date=' + $('release_date').value+
								'&name=' + $('release_name').value+'&release_id=' + $('release_id').value;
			}
			new Ajax.Request(url,
			  {
			    method:'get',
			    onSuccess: (function(transport, json) {
            	if (json.valid) {
            		this.submit();
                	
            	} else {
            		$('feedback').innerHTML = json.msg;
            		Element.scrollTo('feedback');
            	}
        	   }).bind(this) 
			  });
			  Event.stop(evt);
              return false;
		});	
	
});
		