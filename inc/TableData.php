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

  function set_filter($rules) {
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

    $this->current_data = opt_sort($this->orig_data, $this->current_sort);
    return $this->current_data;
  }
}
