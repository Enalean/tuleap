<?php
require_once('pre.php');    
require_once('www/admin/admin_utils.php');
require_once('common/event/EventManager.class.php');

site_admin_header(array('title'=>$GLOBALS['Language']->getText('admin_groupedit','title')));
session_require(array('group'=>'1','admin_flags'=>'A'));
$request = HTTPRequest::instance();
$em = EventManager::instance();

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$params = array('group_id' => $group_id,
                'span' =>&$spanArray,
                'html' => &$htmlArray
);

$em->processEvent('show_pending_documents', $params);
?>
<FORM action="?" method="POST">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<?php echo '<h3>'.$GLOBALS['Language']->getText('admin_show_pending_documents','pending_doc').'</h3>'; ?>
        <div class="systeme_onglets">
            <div class="onglets">
            <?php
            if (isset($params['span']) && $params['span']) {
                foreach($params['span'] as $span){
                    echo $span;
                }
            }
            ?>
            </div>
            <div class="contenu_onglets">
            <?php 
            if (isset($params['html']) && $params['html']) {
                foreach($params['html'] as $html) {
                    echo $html;
                }
            }
            ?>
            </div>
         </div>
         <script type='text/javascript'>
        //<!--
                var anc_onglet = 'version';
                change_onglet(anc_onglet);
        //-->
        </script>
</FORM>
<?php 
site_admin_footer(array());
?>