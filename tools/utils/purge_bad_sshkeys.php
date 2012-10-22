<?php

require_once 'pre.php';

$sql = 'SELECT user_id, user_name, realname, email, authorized_keys FROM user WHERE authorized_keys != "" AND authorized_keys IS NOT NULL';
$res = db_query($sql);
while ($row = db_fetch_array($res)) {
    $valid_keys = array();
    $keys = array_filter(explode('###', $row['authorized_keys']));
    foreach ($keys as $key) {
        $key_file = '/var/tmp/codendi_cache/ssh_key_check';
        $written  = file_put_contents($key_file, $key);
        if ($written === strlen($key)) {
            $return = 1;
            $output = array();
            exec("ssh-keygen -l -f $key_file 2>&1", $output, $return);
            if ($return === 0) {
                $valid_keys[] = $key;
            }
        }
    }
    $str_valid_keys = implode('###', $valid_keys);
    if ($str_valid_keys !== $row['authorized_keys']) {
        echo "Update user (".$row['user_id'].") ".$row['user_name']." ".$row['realname']." ".$row['email'].PHP_EOL;
        $sql = 'UPDATE user SET authorized_keys = "'.db_es($str_valid_keys).'" WHERE user_id = '.$row['user_id'];
        db_query($sql);
    }
}

?>
