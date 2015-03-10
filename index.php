<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
Header("Content-Type: text/html; charset=utf-8");

$data = array();
$f = fopen("data.csv", "r");
$headers = fgetcsv($f);

while($r = fgetcsv($f)) {
  $data[] = array_combine($headers, $r);
}
fclose($f);

$def = array(
  'BASIS_NAME'		=> array(
    'name'		  => "Name",
  ),
  'BASIS_TYP'		=> array(
    'name'		  => "Type"
  ),
  'BAUJAHR'		=> array(
    'name'		  => "Date of construction",
  ),
  'DENKMAL'		=> array(
    'name'		  => "Is Memorial?",
    'type'		  => 'group',
    'format'		  => "Is Memorial? [DENKMAL]",
  ),
);

$table = new table($def, $data);

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
