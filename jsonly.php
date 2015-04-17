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
    'sort'		  => true,
  ),
  'BASIS_TYP'		=> array(
    'name'		  => "Type"
  ),
  'BAUJAHR'		=> array(
    'name'		  => "Date of construction",
    'sort'		  => array(
      'type'		    => 'numeric',
      'dir'		    => 'asc',
      'weight'		    => -1,
    ),
  ),
  'DENKMAL'		=> array(
    'name'		  => "Is Memorial?",
    'type'		  => 'group',
    'format'		  => "Is Memorial? [DENKMAL]",
  ),
);

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
?>

var t = new table(def, data);
//alert(t.columns());
document.getElementById("content").innerHTML = t.show();
</script>
  </body>
</html>
