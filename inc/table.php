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
	case "group":
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
      case "twig":
        return twig_render_custom($format, $data);
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

	if(array_key_exists('type', $v))
	  $r['type'] = $v['type'];

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
	$this->aggregate_values($data[$k], $agg[$k], $v['columns']);
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
      elseif($v['type'] == "group") {
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
      case "twig":
        break;
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
    $rows = array();
    $groups = array();
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

    $data = $this->data;
    $sorts = array();
    foreach($this->def as $k=>$def) {
      if(array_key_exists('sort', $def)) {
	if($def['sort'] === true) {
	  $sorts[] = array(
	    'key'		=> $k,
	    'type'		=> "alpha",
	    'weight'		=> 0
	  );
	}
	else {
	  $s = $def['sort'];
	  $s['key'] = $k;
	  $sorts[] = $s;
	}
      }
    }

    $sorts = weight_sort($sorts);

    usort($data, function($a, $b) use ($sorts) {
      foreach($sorts as $s) {
	$dir = 1;
	if(array_key_exists('dir', $s))
	  $dir = $s['dir'] == 'desc' ? -1 : 1;

	switch(!array_key_exists('type', $s) ? null : $s['type']) {
	  case 'num':
	  case 'numeric':
	    if((float)$a[$s['key']] == (float)$b[$s['key']])
	      continue;

	    $c = (float)$a[$s['key']] > (float)$b[$s['key']] ? 1 : -1;
	    return $c * $dir;

	  case 'nat':
	    $c = strnatcmp($a[$s['key']], $b[$s['key']]);

	    if($c === 0)
	      continue;

	    return $c * $dir;

	  case 'case':
	    $c = strcasecmp($a[$s['key']], $b[$s['key']]);

	    if($c === 0)
	      continue;

	    return $c * $dir;

	  case 'alpha':
	  default:
	    $c = strcmp($a[$s['key']], $b[$s['key']]);

	    if($c === 0)
	      continue;

	    return $c * $dir;
	}
      }
    });

    foreach($data as $rowid=>$rowv) {
      $tr=$this->build_tr($rowv);

      if($has_aggregate) {
	$this->aggregate_values($rowv, $agg);
      }

      $group = array();
      $group_value = array();
      $row = array();
      foreach($this->print_values($rowv, $tr) as $elem) {
	if(array_key_exists('type', $elem) && in_array($elem['type'], array("group"))) {
	  if($elem['type'] == "group")
	    $group[] = $elem;
	    $group_value[] = $elem['value'];
	}
	else
	  $row[] = $this->print_row($elem, $mode);
      }

      $group_value = implode("|", $group_value);
      $groups[$group_value] = $group;
      $rows[$group_value][] = $row;
    }

    foreach($rows as $group_value=>$group_rows) {
      switch($mode) {
	case "html":
	  $ret.="  <tr class='group'>\n";
	  $ret.="<td colspan='". sizeof($group_rows[0]) ."'>{$group_value}</td>";
	  $ret.="  </tr>\n";
	  break;
	case "csv":
	  $ret.=printcsv(array($group_value), $csv_conf[0], $csv_conf[1]);
	  break;
      }

      foreach($group_rows as $row) {
	switch($mode) {
	  case "html":
	    $ret.="  <tr>\n";
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
