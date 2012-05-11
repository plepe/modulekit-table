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

  function columns($def=null) {
    if($def===null)
      $def=$this->def;
    $columns=0;

    foreach($def as $k=>$v) {
      switch($v['type']) {
	case "multiple":
	  $columns+=$this->columns($v['columns']);
	  break;
	default:
	  $columns++;
      }
    }

    return $columns;
  }

  function levels($def=null) {
    if($def===null)
      $def=$this->def;
    $sub_levels=0;

    foreach($def as $k=>$v) {
      switch($v['type']) {
	case "multiple":
	  $l=$this->levels($v['columns']);
	  if($l>$sub_levels)
	    $sub_levels=$l;
	  break;
      }
    }

    return $sub_levels+1;
  }

  function print_values($data, $tr, $def=null) {
    if($def===null)
      $def=$this->def;

    foreach($def as $k=>$v) {
      $value=$data[$k];

      if($v['type']=="multiple")
	$ret.=$this->print_values($data[$k], $tr, $v['columns']);
      else {
	if($v['format'])
	  $value=strtr($v['format'], $tr);

	$ret.="    <td class='$k'>{$value}</td>\n";
      }
    }

    return $ret;
  }

  function print_headers($level, $def=null, $maxlevel=null) {
    if($maxlevel===null)
      $maxlevel=$this->levels($def);
    if($def===null)
      $def=$this->def;

    foreach($def as $k=>$v) {
      if($v['type']=="multiple") {
	if($level==0) {
	  $cols=$this->columns($v['columns']);
	  $ret.="    <th class='$k' colspan='$cols'>{$v['name']}</th>\n";
	}
	else
	  $ret.=$this->print_headers($level-1, $v['columns'], $maxlevel-1);
      }
      else {
	if($level==0) {
	  $ret.="    <th class='$k' rowspan='$maxlevel'>{$v['name']}</th>\n";
	}
      }
    }

    return $ret;
  }

  function show() {
    $ret="<table class='studidaten'>";

    for($l=0; $l<$this->levels(); $l++) {
      $ret.="  <tr>\n";
      $ret.=$this->print_headers($l);
      $ret.="  </tr>\n";
    }

    foreach($this->data as $rowid=>$row) {
      $tr=array();
      foreach($row as $k=>$v) {
	$tr["[$k]"]=$v;
      }

      $ret.="  <tr>\n";

      $ret.=$this->print_values($row, $tr);
//      foreach($this->def as $k=>$v) {
//	$value="";
//	if(isset($row[$k]))
//	  $value=$row[$k];
//
//	if($v['format'])
//	  $value=strtr($v['format'], $tr);
//
//	$ret.=$this->print_values($v, $tr);
//	$ret.="    <td class='$k'>{$value}</td>\n";
//      }
      $ret.="  </tr>\n";
    }

    $ret.="</table>\n";

    return $ret;
  }
}
