```php
$table = new table($definition, $data, [$options]);
print $table->show([$mode, [$param]]);
```

The following options are available:

Key             | Description | Possible values (highlight = default)
----------------|-------------|---------------------------------------
template_engine | Which templating engine to use | **internal**, twig[1]

[1] If you want to use [Twig](http://twig.sensiolabs.org/) as templating engine, include the module 'twig' of [modulekit-base](https://github.com/plepe/modulekit-base).

```php
$def = column definition, array:
"column_id"=>array(
  "name"=> "Name of Column",
  "format"=> (optional), define format of field using other values as 
                         replacement patterns in [ ]
                         e.g. "<a href='foo.html?id=[id]'>[name]</a>"

$data = table data, array:
"row_id"=>array(
   "column_id"=>array("foobar")
)

$table = new table($def, $data);
print $table->show();
```

Example using the Twig templating engine:

```php
$def = column definition, array:
"column_id"=>array(
  "name"=> "Name of Column",
  "format"=> (optional), define format of field using other values as
                         replacement patterns in [ ]
                         e.g. "<a href='foo.html?id={{ id }}'>{{ name }}</a>"

$data = table data, array:
"row_id"=>array(
   "column_id"=>array("foobar")
)

$table = new table($def, $data, array("template_engine"=>"twig"));
print $table->show();
```

Testdata
========
Source for the test data in data.csv is the Magistratsabteilung 31 - Wiener
Wasser; a list of historical fountains in Vienna. CC-BY-SA 3.0 AT.
Source: https://www.data.gv.at/katalog/dataset/ce6baef9-42a8-4c09-8d50-99be6ac4ca9e
