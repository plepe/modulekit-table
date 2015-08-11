<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
Header("Content-Type: text/html; charset=utf-8");
include "def.php";

$data = array();
$f = fopen("data.csv", "r");
$headers = fgetcsv($f);

while($r = fgetcsv($f)) {
  $data[] = array_combine($headers, $r);
}
fclose($f);

?>
<html>
  <head>
    <title>Framework Example</title>
    <?php print modulekit_to_javascript(); /* pass modulekit configuration to JavaScript */ ?>
    <?php print modulekit_include_js(); /* prints all js-includes */ ?>
    <?php print modulekit_include_css(); /* prints all css-includes */ ?>
  </head>
  <body>
<div id='content'></div>
<script type='text/javascript'>
<?php
print "var data = " . json_encode($data) . ";\n";
print "var def = " . json_encode($def) . ";\n";
print "var filter = " . json_encode($filter) . ";\n";
?>

var t = new table(def, data);
//alert(t.columns());
if(filter)
  t.set_filter(filter);
t.show(function(result) {
  document.getElementById("content").innerHTML = result;
});
</script>
  </body>
</html>
