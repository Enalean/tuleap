<?php
require_once('pre.php');
$request =& HTTPRequest::instance();


$nb_max = 50;

$s = db_escape_string(strtolower($request->get('search_for')));

$sql = "SELECT realname, user_name AS name, email, 0 AS is_list
        FROM user
        WHERE user.status IN ('A', 'R') 
          AND (
              LOWER(user.user_name) LIKE '%$s%' 
             OR
              LOWER(user.realname) LIKE '%$s%' 
             OR
              LOWER(user.email) LIKE '%$s%' 
              )
UNION
SELECT 'Mailing List' AS realname, list_name AS name, list_name AS email, 1 AS is_list
FROM mail_group_list
WHERE status = 1
  AND is_public = 1
  AND (
      LOWER(list_name) LIKE '%$s%'
      )
ORDER BY name
LIMIT ".($nb_max+1);
        
$res = db_query($sql);
echo db_error();
echo '<ul>';
$i = 0;
while(($data = db_fetch_array($res)) && $i++ < $nb_max) {
    $ml_informal = $data['is_list'] ? 'informal' : '';
    $us_informal = $data['is_list'] ? '' : 'informal';
    $email = $data['is_list'] ? $data['email'].'@'.$GLOBALS['sys_lists_host'] : $data['email'];
    if (!$data['is_list']) {
        echo '<li><span style="font-weight:bold" class="'. $ml_informal .'">'. $data['name'] .'</span><span class="informal"> ('. $data['realname'] .')</span><div class="'. $us_informal .'"">'. $email .'</div></li>';
    } else {
        echo '<li><div style="font-style:italic;font-weight:bold;" class="'. $us_informal .'"">'. $email .'</div></li>';
    }
}
echo '</ul>';
if ($i >= 25) {
    echo '<div><span class="informal" style="font-style:italic;">There are more than '. $nb_max .' results. Please affine your request</div>';
}
?>
