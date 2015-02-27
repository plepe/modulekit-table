<?
class table {
  function __construct($def, $data, $options=array()) {
    $this->def=$def;
    $this->data=$data;
    $this->mode="html";
    $this->options = $options;
    $this->agg=array();

    if(!array_key_exists('template_engine', $this->options))
      $this->options['template_engine'] = 'internal';
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

  function replace($data, $tr, $format) {
    switch($this->options['template_engine']) {
      case "internal":
      default:
	return strtr($format, $tr);
    }
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
	  $value = $this->replace($data, $tr, $v['format']);

	$r=array("class"=>$k, "value"=>$value);

	if(isset($v['link']))
	  $r['link'] = $this->replace($data, $tr, $v['link']);

	$ret[]=$r;
      }
    }

    return $ret;
  }

  function print_aggregate($agg, $def=null) {
    $ret=array();
    if($def===null)
      $def=$this->def;

    foreach($def as $k=>$v) {
      $value=$agg[$k];

      if($v['type']=="multiple")
	$ret=array_merge($ret, $this->print_aggregate($agg[$k], $v['columns']));
      else {
	$ret[]=array("class"=>$k, "value"=>$value);
      }
    }

    return $ret;
  }

  function aggregate_check($def=null) {
    if($def===null)
      $def=$this->def;

    foreach($def as $k=>$v) {
      $value=$data[$k];

      if($v['type']=="multiple") {
	$ret=$this->aggregate_check($v['columns']);
	if($ret)
	  return true;
      }
      else {
	$value=null;
	if(isset($v['agg'])) {
	  return true;
	}
      }
    }

    return false;
  }

  function aggregate_values($data, &$agg, $def=null) {
    if($def===null)
      $def=$this->def;

    foreach($def as $k=>$v) {
      $value=$data[$k];

      if($v['type']=="multiple")
	$this->aggregate_values($data[$k], &$agg[$k], $v['columns']);
      else {
	$value=null;
	if(isset($v['agg'])) switch($v['agg']) {
	  case "count_values":
	    if(isset($data[$k])&&($data[$k]))
	      $agg[$k]++;
	}
      }
    }
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
	else {
	  $ret[]=null;
	}
      }
    }

    return $ret;
  }

  function print_row($elem, $mode) {
    switch($mode) {
      case "html":
	$r=$elem['value'];

	if(isset($elem['link']))
	  $r="<a href='{$elem['link']}'>{$r}</a>";

	return "    <td class='{$elem['class']}'>{$r}</td>\n";
	break;
      case "csv":
	return $elem['value'];
	break;
    }
  }

  function build_tr($rowv, $prefix="") {
    switch($this->options['template_engine']) {
      case "internal":
      default:
	$tr=array();
	foreach($rowv as $k=>$v) {
	  if(is_array($v))
	    $tr=array_merge($tr, $this->build_tr($v, "{$prefix}{$k}."));
	  else
	    $tr["[{$prefix}{$k}]"]=$v;
	}
    }

    return $tr;
  }

  function show($mode="html", $param=null) {
    $has_aggregate=$this->aggregate_check();

    switch($mode) {
      case "html":
	$ret="<table class='table'>";
	break;
      case "csv":
        if($param!=null)
	  $csv_conf=$param;
	else
	  $csv_conf=array(",", "\"", "UTF-8");
	$ret="";
        break;
      default:
        print "Table: Invalid mode '$mode'\n";
    }

    $agg=array();
    for($l=0; $l<$this->levels(); $l++) {
      switch($mode) {
	case "html":
	  $ret.="  <tr>\n";
	  break;
      }

      foreach($this->print_headers($l) as $elem) {
	switch($mode) {
	  case "html":
	    if($elem!=null) {
	      $ret.="<th class='{$elem['class']}'";
	      if(isset($elem['colspan']))
		$ret.=" colspan='{$elem['colspan']}'";
	      if(isset($elem['rowspan']))
		$ret.=" rowspan='{$elem['rowspan']}'";
	      $ret.=">{$elem['value']}</th>\n";
	    }
	    break;
	  case "csv":
	    $colspan=1;
	    if(isset($elem['colspan']))
	      $colspan=$elem['colspan'];

	    for($i=0; $i<$colspan; $i++) {
	      if($elem!=null)
		$row[]=$elem['value'];
	      else
		$row[]="";
	    }
	}
      }

      switch($mode) {
	case "html":
	  $ret.="  </tr>\n";
	  break;
	case "csv":
	  $ret.=printcsv($row, $csv_conf[0], $csv_conf[1]);
	  $row=array();
	  break;
      }

    }

    foreach($this->data as $rowid=>$rowv) {
      $tr=$this->build_tr($rowv);

      switch($mode) {
	case "html":
	  $ret.="  <tr>\n";
	  break;
      }

      if($has_aggregate) {
	$this->aggregate_values($rowv, &$agg);
      }

      foreach($this->print_values($rowv, $tr) as $elem) {
	$row[]=$this->print_row($elem, $mode);
      }

      switch($mode) {
	case "html":
	  $ret.=implode("\n", $row);
	  $ret.="  </tr>\n";
	  $row=array();
	  break;
	case "csv":
	  $ret.=printcsv($row, $csv_conf[0], $csv_conf[1]);
	  $row=array();
	  break;
      }
    }

    if($has_aggregate) {
      foreach($this->print_aggregate($agg) as $elem) {
	$row[]=$this->print_row($elem, $mode);
      }

      switch($mode) {
	case "html":
	  $ret.="  <tr class='agg'>\n";
	  $ret.=implode("\n", $row);
	  $ret.="  </tr>\n";
	  $row=array();
	  break;
	case "csv":
	  $ret.=printcsv($row, $csv_conf[0], $csv_conf[1]);
	  $row=array();
	  break;
      }
    }

    switch($mode) {
      case "html":
	$ret.="</table>\n";
	break;
    }

    return $ret;
  }
}

function printcsv($row, $delim=",", $encl="\"") {
  $tr=array("&shy;"=>"", "\""=>"\\\"");
  $l=array();

  foreach($row as $r) {
    $r=strtr($r, $tr);

    if(is_numeric($r))
      $l[]="{$r}";
    else
      $l[]="{$encl}{$r}{$encl}";
  }

  return implode($delim, $l)."\r\n";
}
