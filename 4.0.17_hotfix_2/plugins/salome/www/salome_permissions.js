document.observe('dom:loaded', function() {
    
    
    // Test Suite
    
    function check_suite_all_state() {
        if ($('test_suite_add').checked &&
            $('test_suite_modify').checked &&
            $('test_suite_delete').checked) {
            
            // all checkboxes checked => check "all" checkbox
            $('test_suite_all').checked = true;
        } else {
            $('test_suite_all').checked = false;
        }
    }
    
    $('test_suite_all').observe('change', function() {
        var test_suite_all_state = $('test_suite_all').checked;
        $('test_suite_add').checked = test_suite_all_state;
        $('test_suite_modify').checked = test_suite_all_state;
        $('test_suite_delete').checked = test_suite_all_state;
    });
    
    $('test_suite_add').observe('change', function() {
        if ($('test_suite_add').checked) {
            // check add => check modify
            $('test_suite_modify').checked = true;
        } else {
            // uncheck add => uncheck delete
            $('test_suite_delete').checked = false;
        }
        check_suite_all_state();
    });
    
    $('test_suite_modify').observe('change', function() {
        if ($('test_suite_modify').checked) {
            // nothing
        } else {
            // uncheck modify => uncheck add and uncheck delete
            $('test_suite_add').checked = false;
            $('test_suite_delete').checked = false;
        }
        check_suite_all_state();
    });
    
    $('test_suite_delete').observe('change', function() {
        if ($('test_suite_delete').checked ){
            // check delete => check add and check modify
            $('test_suite_add').checked = true;
            $('test_suite_modify').checked = true;
        }
        check_suite_all_state();
    });
    
    
    // Test Campaign
    
    function check_campaign_all_state() {
        if ($('test_campaign_add').checked &&
            $('test_campaign_modify').checked &&
            $('test_campaign_delete').checked &&
            $('test_campaign_execute').checked) {
            
            // all checkboxes checked => check "all" checkbox
            $('test_campaign_all').checked = true;
        } else {
            $('test_campaign_all').checked = false;
        }
    }
    
    $('test_campaign_all').observe('change', function() {
        var test_campaign_all_state = $('test_campaign_all').checked;
        $('test_campaign_add').checked = test_campaign_all_state;
        $('test_campaign_modify').checked = test_campaign_all_state;
        $('test_campaign_delete').checked = test_campaign_all_state;
        $('test_campaign_execute').checked = test_campaign_all_state;
    });
    
    $('test_campaign_add').observe('change', function() {
        if ($('test_campaign_add').checked) {
            // check add => check modify
            $('test_campaign_modify').checked = true;
        } else {
            // uncheck add => uncheck delete
            $('test_campaign_delete').checked = false;
        }
        check_campaign_all_state();
    });
    
    $('test_campaign_modify').observe('change', function() {
        if ($('test_campaign_modify').checked) {
            // nothing
        } else {
            // uncheck modify => uncheck add and uncheck delete
            $('test_campaign_add').checked = false;
            $('test_campaign_delete').checked = false;
        }
        check_campaign_all_state();
    });
    
    $('test_campaign_delete').observe('change', function() {
        if ($('test_campaign_delete').checked ){
            // check delete => check add and check modify
            $('test_campaign_add').checked = true;
            $('test_campaign_modify').checked = true;
        }
        check_campaign_all_state();
    });
    
    $('test_campaign_execute').observe('change', function() {
        check_campaign_all_state();
    });
    
});
