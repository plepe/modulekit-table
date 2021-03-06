<?php
class TableData {
  function __construct($data) {
    $this->orig_data = $data;

    $this->current_sort = array();
    $this->current_filter = array();
    $this->current_data = null;
  }

  function set_sort($rules) {
    $this->current_sort = $rules;
    $this->current_data = null;
  }

  function set_filter($data) {
    $this->current_filter = $rules;
    $this->current_data = null;
  }

  function count() {
    $this->run();

    return sizeof($this->current_data);
  }

  function get($offset=0, $limit=null) {
    $this->run();

    if($offset === null)
      $offset = 0;

    if(($offset === 0) && ($limit === null))
      return $this->current_data;

    return array_slice($this->current_data, $offset, $limit);
  }

  function run() {
    if($this->current_data)
      return;

    $this->current_data = $this->orig_data;

    // add __index value, to maintain value order on equal entries
    $i = 0;
    foreach($this->current_data as $k=>$d) {
      if(is_object($this->current_data[$k]))
        $this->current_data[$k]->__index = $i++;
      else
        $this->current_data[$k]['__index'] = $i++;
    }

    $sorts = &$this->current_sort;
    usort($this->current_data, function($a, $b) use ($sorts) {
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
      return (is_object($a) ? $a->__index : $a['__index'])
             >
             (is_object($b) ? $b->__index : $b['__index']);
    });

    return $this->current_data;
  }
}
