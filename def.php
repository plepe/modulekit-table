<?php
$def = array(
  'BASIS_NAME'		=> array(
    'name'		  => "Name",
    'sortable'		  => true,
  ),
  'BASIS_TYP'		=> array(
    'name'		  => "Type",
    'show_priority'       => 1,
  ),
  'BAUJAHR'		=> array(
    'name'		  => "Date of construction",
    'sort'		  => array(
      'type'		    => 'numeric',
      'dir'		    => 'desc',
      'weight'		    => -1,
    ),
  ),
  'DENKMAL'		=> array(
    'name'		  => "Is Memorial?",
    'type'		  => 'group',
    'format'		  => "Is Memorial? [DENKMAL]",
  ),
);

$filter = array(
  array("key"=>"BAUJAHR", "op"=>">=", "value"=>"1928"),
);
