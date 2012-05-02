<html><head><title>Alpha Channel Test</title>
<script type="text/javascript"><!--
var backgroundcolor = new Array();
backgroundcolor[0] = '#ffffff';
backgroundcolor[1] = '#cccccc';
backgroundcolor[2] = '#888888';
backgroundcolor[3] = '#444444';
backgroundcolor[4] = '#000000';
backgroundcolor[5] = '#aa8888';
backgroundcolor[6] = '#88aa88';
backgroundcolor[7] = '#8888aa';
function changebg(color) { document.bgColor = backgroundcolor[color]; }
//--></script>
</head><body bgcolor="#8888aa">
<hr>
bgcolor:
<script type="text/javascript"><!--
for (var n = 0; n < backgroundcolor.length; n++)
{ document.write(
' <a href="#" onMouseOver="javascript:changebg(' + n + ')">' + backgroundcolor[n] + '</a>'
); }
//--></script>
<?

function find_pngs($dir) {
  $file_list = '';
  $stack[] = $dir;
  while ($stack) {
    $current_dir = array_pop($stack);
    $pngs = false;
    if ($dh = opendir($current_dir)) {
      while (($file = readdir($dh)) !== false) {
        if ($file !== '.' AND $file !== '..') {
          $current_file = "{$current_dir}/{$file}";
          if (is_file($current_file)) {
            if  (!(strcmp(substr($file, -4), ".png"))) {
              if (!$pngs) {
                print "<hr /><h4>\n{$current_dir}</h4>\n";
                $pngs = true;
              }
              print "<img src=\"{$current_dir}/" . urlencode($file) . "\" alt=\"{$file}\" />\n";
            }
          }
          elseif (is_dir($current_file)) {
            $stack[] = $current_file;
          }
        }
      }
    }
  }
}

find_pngs(".");
?>
<hr>
bgcolor:
<script type="text/javascript"><!--
for (var n = 0; n < backgroundcolor.length; n++)
{ document.write(
' <a href="#" onMouseOver="javascript:changebg(' + n + ')">' + backgroundcolor[n] + '</a>'
); }
//--></script>
<hr>
</body></html>
