<?php
class table {
  function __construct($def, $data, $options=array()) {
    $this->def=$def;
    $this->data=$data;
    $this->mode="html";
    $this->options = $options;
    if(!array_key_exists("id", $this->options))
      $this->options['id'] = "t" . rand();
    $this->id =  $this->options['id'];
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
	case "hidden":
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
      $value=htmlspecialchars($data[$k]);

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
	  $ret[]=array("type"=>"head", "class"=>$k, "colspan"=>$cols, "value"=>$v['name']);
	}
	else
	  $ret=array_merge($ret, $this->print_headers($level-1, $v['columns'], $maxlevel-1));
      }
      elseif(in_array($v['type'], array("group", "hidden"))) {
      }
      else {
	if($level==0) {
	  $ret[]=array("type"=>"head", "class"=>$k, "rowspan"=>$maxlevel, "value"=>$v['name']);
	}
	else {
	  $ret[]=null;
	}
      }
    }

    return $ret;
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
	    $tr["[{$prefix}{$k}]"]=htmlspecialchars($v);
	}
    }

    return $tr;
  }

  function show($mode="html", $param=null) {
    $has_aggregate=$this->aggregate_check();
    $result = array();

    $agg=array();
    $rows = array();
    $groups = array();
    for($l=0; $l<$this->levels(); $l++) {
      $current_row = $this->print_headers($l);

      $result[] = array("type" => "head{$l}", "values" => $current_row);
    }

    $data = $this->data;
    $sorts = array();
    $has_groups = false;
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

      if(array_key_exists('type', $def) && ($def['type'] == 'group'))
	$has_groups = true;
    }

    $sorts = weight_sort($sorts);

    // add __index value, to maintain value order on equal entries
    $i = 0;
    foreach($data as $k=>$d) {
      $data[$k]['__index'] = $i++;
    }

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

      // equal entries for sorting -> maintain value order
      return $a['__index'] > $b['__index'];
    });

    $count = sizeof($data);
    if(array_key_exists('limit', $param) && ($param['limit'] <= sizeof($data)))
      $count = $param['limit'];

    for($rowid = 0; $rowid < $count; $rowid++) {
      $rowv = $data[$rowid];
      $tr=$this->build_tr($rowv);

      if($has_aggregate) {
	$this->aggregate_values($rowv, $agg);
      }

      $group = array();
      $group_value = array();
      $row = array();
      foreach($this->print_values($rowv, $tr) as $elem) {
	if(array_key_exists('type', $elem) && in_array($elem['type'], array("group", "hidden"))) {
	  if($elem['type'] == "group") {
	    $group[] = $elem;
	    $group_value[] = $elem['value'];
	  }
	}
	else
	  $row[] = $elem;
      }

      $group_value = implode("|", $group_value);
      $groups[$group_value] = $group;
      $rows[$group_value][] = $row;
    }

    foreach($rows as $group_value=>$group_rows) {
      $current_row = array();
      if($has_groups) {
        $result[] = array(
          'type' => 'group',
          'values' => array(
            array(
              'value' => $group_value,
              'colspan' => sizeof($group_rows[0]),
            )
          ),
        );
      }

      foreach($group_rows as $row) {
        $result[] = array(
          'type' => 'element',
          'values' => $row,
        );
      }
    }

    if($has_aggregate) {
      $result[] = array(
        'type' => 'agg',
        'values' => $this->print_aggregate($agg),
      );
    }

    // print_r($result);
    if($mode == "html")
      return $this->print_html($result, $param);
    elseif($mode == "html-transposed")
      return $this->print_html_transposed($result, $param);
    else
      return $this->print_csv($result, $param);
  }

  function print_html($result, $param=array()) {
    $ret = "<table class='table' id='{$this->id}'>";

    $odd = false;
    foreach($result as $row) {
      switch($row['type']) {
        case "element":
          $ret .= "  <tr class='". ($odd ? "odd" : "even") ."'>\n";
          break;
        default:
          $ret .= "  <tr class='{$row['type']}'>\n";
      }

      foreach($row['values'] as $el) {
        if(array_key_exists('type', $el) && ($el['type'] == 'head')) {
          $ret .= "    <th ";
          $end = "</th>";
        }
        else {
          $ret .= "    <td ";
          $end = "</td>";
        }

        if(array_key_exists('colspan', $el))
          $ret .= "colspan='{$el['colspan']}' ";
        if(array_key_exists('rowspan', $el))
          $ret .= "rowspan='{$el['rowspan']}' ";

        $ret .= "class='{$el['class']}'>";

        if(array_key_exists("link", $el))
          $ret .= "<a href='" . $el['link'] . "'>" . $el['value'] . "</a>";
        else
          $ret .= $el['value'];

        $ret .= "{$end}\n";

      }

      $ret .= "  </tr>\n";
      if($row['type'] == "element")
        $odd = !$odd;
    }

    $ret .= "</table>\n";

    $ret .= "<script type='text/javascript'>\n";
    $ret .= "var table_{$this->id} = new table(". json_encode($this->def) . ", null, " . json_encode($this->options) . ")\n";
    $ret .= "</script>\n";

    return $ret;
  }

  function print_html_transposed($result, $param=array()) {
    $ret = "<table class='table transposed'>";
    $cols = array();

    $odd = "even";
    foreach($result as $row) {
      $i = 0;
      foreach($row['values'] as $el) {

        if(array_key_exists('type', $el) && ($el['type'] == 'head')) {
          $cols[$i] .= "    <th ";
          $end = "</th>";
        }
        else {
          $cols[$i] .= "    <td ";
          $end = "</td>";
        }

        if(array_key_exists('colspan', $el))
          $cols[$i] .= "rowspan='{$el['colspan']}' ";
        if(array_key_exists('rowspan', $el))
          $cols[$i] .= "colspan='{$el['rowspan']}' ";

        $cols[$i] .= "class='{$el['class']}";

        switch($row['type']) {
          case "element":
            $cols[$i] .= " {$odd}";
            break;
          default:
            $cols[$i] .= " {$row['type']}";
        }

        $cols[$i] .= "'>";

        if(array_key_exists("link", $el))
          $cols[$i] .= "<a href='" . $el['link'] . "'>" . $el['value'] . "</a>";
        else
          $cols[$i] .= $el['value'];

        $cols[$i] .= "{$end}\n";

        $i++;
      }

      if($row['type'] == "element")
        $odd = ($odd == "even" ? "odd": "even");
    }

    foreach($cols as $c) {
      $ret .= "  <tr>\n". $c . "  </tr>\n";
    }

    $ret .= "</table>\n";
    return $ret;
  }

  function print_csv($result, $param=array()) {
    $ret = "";

    if($param!=null)
      $csv_conf=$param;
    else
      $csv_conf=array(",", "\"", "UTF-8");

    foreach($result as $row) {
      $to_print = array();
      foreach($row['values'] as $el) {
        $colspan = 1;
        if(array_key_exists('colspan', $el))
          $colspan = $el['colspan'];

        for($i = 0; $i < $colspan; $i++)
          $to_print[] = $el['value'];
      }

      $ret .= printcsv($to_print, $csv_conf[0], $csv_conf[1]);
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
