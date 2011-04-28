document.observe('dom:loaded', function() {
    
    $('enable_proxy').observe('change', function() {
    	var state = $('enable_proxy').checked; 
        $('proxy').disabled = ! state;
        $('proxy_user').disabled = ! state;
        $('proxy_password').disabled = ! state;
    });
    
});
