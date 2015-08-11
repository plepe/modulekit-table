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

$table = new table($def, $data);
if(isset($filter))
  $table->set_filter($filter);

?>
<html>
  <head>
    <title>Framework Example</title>
    <?php print modulekit_to_javascript(); /* pass modulekit configuration to JavaScript */ ?>
    <?php print modulekit_include_js(); /* prints all js-includes */ ?>
    <?php print modulekit_include_css(); /* prints all css-includes */ ?>
  </head>
  <body>
<?php
print $table->show();

//print_r($data);
?>
  </body>
</html>
