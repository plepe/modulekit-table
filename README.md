Introduction
============
```php
$table = new table($definition, $data, [$options]);
print $table->show([$mode, [$param]]);
```

$defintion
----------
Defines the columns of the table. It is an assoc. array with the column id as
keys and another assoc. array setting the properties of each column.

The following properties for each column are available:

Property        | Description
----------------|-------------
name            | Name of column, will be printed in the header
type            | "default": a normal column (default); "multiple": column has several sub columns (see property "columns"); "group": This column won't appear as separate column, but all rows with the same value in this field will be grouped together and will get a sub-header.
format          | Defines an output format for this field (see below under "Format"). If omitted, the value of the data with this column id will be used.

$data
-----
A list of assoc. arrays, e.g.:

```json
[
  {
    "id": "30071",
    "name": "Schubertbrunnen",
    "typ": "Monumentalbrunnen",
    "year": "1928",
  },
  {
    "id": "30072",
    "name": "Opernbrunnen",
    "typ": "Monumentalbrunnen",
    "year": "1868",
  },
  ...
]
```

$options
--------
The following options are available:

Key             | Description | Possible values (highlight = default)
----------------|-------------|---------------------------------------
template_engine | Which templating engine to use | **internal**, twig[1]

[1] If you want to use [Twig](http://twig.sensiolabs.org/) as templating engine, include the module 'twig' of [modulekit-base](https://github.com/plepe/modulekit-base).

Format
======
There are currently two ways how a column value is formatted. Either by using
the internal templating engine (which is rather limited) or the Twig templating
engine. Which engine is used is defined by the global option "template_engine".

Internal templating engine
--------------------------
All fields from the data are available as patterns enclosed in brackets: e.g. `[id]`.

Twig templating engine
----------------------
Fields are enclosed in double curly brackets, e.g. `{{ id }}`. There's the
possibility to use loops, conditions and filters. See the [Twig
homepage](http://twig.sensiolabs.org/) for details.

Examples
========
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
