<?
/*
 *  $def = column definition, array:
 *  "column_id"=>array(
 *    "name"=>
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
      $ret.="  <tr>\n";
      foreach($this->def as $k=>$v) {
	$ret.="    <td class='$k'>{$row[$k]}</th>\n";
      }
      $ret.="  </tr>\n";
    }

    $ret.="</table>\n";

    return $ret;
  }
}
