<?php

$a = 0;
function create_dir_rec($i, $n) {
    global $a;
    if ($i < $n) {
        echo '<item type="folder"><properties><title>Folder #'. ($a++) .'</title><description>Mass Import</description></properties>';
        echo '<item type="link"><properties><title>Link #'. ($a++) .'</title></properties><url>http://codendi.com</url></item>';
        for($j = 0 ; $j < $n ; ++$j) {
            create_dir_rec($i + 1, $n);
        }
        echo '</item>';
    }
}

echo '<?xml version="1.0" encoding="UTF-8" ?>
<docman><item type="folder"><properties><title>Project Documentation</title></properties>';
create_dir_rec(0, 7, '/tmp/');
echo '</item></docman>';
?>
