<?
/*
 *  $def = column definition, array:
 *  "column_id"=>array(
 *    "name"=>
 *    "format"=> (optional), define format of field using other values as 
 *                           replacement patterns in [ ]
 *                           e.g. "<a href='foo.html?id=[id]'>[name]</a>"
 *
 *  $data = table data, array:
 *  "row_id"=>array(
 *     "column_id"=>array("foobar")
 *  )
 */

class table {
  function __construct($def, $data) {
    $this->def=$def;
    $this->data=$data;
  }

  function show() {
    $ret="<table class='studidaten'>";

    $ret.="  <tr>\n";
    foreach($this->def as $k=>$v) {
      $ret.="    <th class='$k'>{$v['name']}</th>\n";
    }
    $ret.="  </tr>\n";

    foreach($this->data as $rowid=>$row) {
      $tr=array();
      foreach($row as $k=>$v) {
	$tr["[$k]"]=$v;
      }

      $ret.="  <tr>\n";
      foreach($this->def as $k=>$v) {
	$value="";
	if(isset($row[$k]))
	  $value=$row[$k];

	if($v['format'])
	  $value=strtr($v['format'], $tr);

	$ret.="    <td class='$k'>{$value}</th>\n";
      }
      $ret.="  </tr>\n";
    }

    $ret.="</table>\n";

    return $ret;
  }
}
