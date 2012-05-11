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
    $ret=array();
    if($def===null)
      $def=$this->def;

    foreach($def as $k=>$v) {
      $value=$data[$k];

      if($v['type']=="multiple")
	$ret=array_merge($ret, $this->print_values($data[$k], $tr, $v['columns']));
      else {
	if($v['format'])
	  $value=strtr($v['format'], $tr);

	$ret[]=array("class"=>$k, "value"=>$value);
      }
    }

    return $ret;
  }

  function print_headers($level, $def=null, $maxlevel=null) {
    if($maxlevel===null)
      $maxlevel=$this->levels($def);
    if($def===null)
      $def=$this->def;
    $ret=array();

    foreach($def as $k=>$v) {
      if($v['type']=="multiple") {
	if($level==0) {
	  $cols=$this->columns($v['columns']);
	  $ret[]=array("class"=>$k, "colspan"=>$cols, "value"=>$v['name']);
	}
	else
	  $ret=array_merge($ret, $this->print_headers($level-1, $v['columns'], $maxlevel-1));
      }
      else {
	if($level==0) {
	  $ret[]=array("class"=>$k, "rowspan"=>$maxlevel, "value"=>$v['name']);
	}
      }
    }

    return $ret;
  }

  function show() {
    $ret="<table class='studidaten'>";

    for($l=0; $l<$this->levels(); $l++) {
      $ret.="  <tr>\n";
      foreach($this->print_headers($l) as $elem) {
	$ret.="<th class='{$elem['class']}'";
	if(isset($elem['colspan']))
	  $ret.=" colspan='{$elem['colspan']}'";
	if(isset($elem['rowspan']))
	  $ret.=" rowspan='{$elem['rowspan']}'";
	$ret.=">{$elem['value']}</th>\n";
      }
      $ret.="  </tr>\n";
    }

    foreach($this->data as $rowid=>$row) {
      $tr=array();
      foreach($row as $k=>$v) {
	$tr["[$k]"]=$v;
      }

      $ret.="  <tr>\n";

      foreach($this->print_values($row, $tr) as $elem) {
        $ret.="    <td class='{$elem['class']}'>{$elem['value']}</td>\n";
      }
    }

    $ret.="</table>\n";

    return $ret;
  }
}
