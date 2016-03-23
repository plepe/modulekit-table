Introduction
============
```php
$table = new table($definition, $data, [$options]);
print $table->show();
```

If an HTTP parameter 'sort' is submitted, a sort for this key gets higher priority (-10000). If additionally, a parameter 'sort_dir' is passed, the direction will be altered.

$defintion
----------
Defines the columns of the table. It is an assoc. array with the column id as
keys and another assoc. array setting the properties of each column.

The following properties for each column are available:

Property        | Description
----------------|-------------
name            | Name of column, will be printed in the header
type            | "default": a normal column (default); "hidden": hidden from output (but may be used for sorting); "multiple": column has several sub columns (see property "columns"); "group": This column won't appear as separate column, but all rows with the same value in this field will be grouped together and will get a sub-header.
auto_escape     | If the content should automatically be escaped for HTML output (default: true) (currently: PHP only)
format          | Defines an output format for this field (see below under "Format"). If omitted, the value of the data with this column id will be used.
sort            | Sort by this value. Either boolean (default: false: no sort) or an assoc. array further defining sort criteria: "type": "alpha" (default), "num"/"numeric", "case" (alphabetic, but case insenstive), "nat" (natural sort algorithm); "dir": "asc" (default), "desc"; "weight": defines order of sorting, if there are several sort options (the lower the value the more important; default 0); "key": specify alternative key for sorting.
sortable        | Like sort, but does not sort by this column by default.
show_priority   | Defines priority that this column is shown (on narrow browser windows, only columns with high priority is shown). If no 'show_priority' is defined / is null, the column will always be shown.
html_attributes | Additional html attributes which will be added to the column, e.g. `style='background: black;'`.

$data
-----
Eiter a list of assoc. arrays OR an object of class TableData (see below) OR an object which implements the same interface.

If it is a list of assoc. arrays, this could look like this:

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

If the individual entries are objects, the function 'view()' will be used to
get the entries' values.

$options
--------
The following options are available:

Key             | Description | Possible values (highlight = default)
----------------|-------------|---------------------------------------
template_engine | Which templating engine to use | **internal**, twig[1]
id              | ID of table (string). If undefined a random ID will be used. |

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

Functions
=========
show([$mode, [$param]])
-----------------------

* Mode: 'html' or 'html-transposed' or 'csv'
* Param: a hash with:
** 'offset': start at element n
** 'limit': only show n elements
** 'show_table_header': whether the table header should be shown (true/false, default true)

In JS mode an additional paramter `callback` is required, which will be called with the resulting table.

set_sort(sorts)
---------------
Override default sort. E.g.:
```json
[
  {
    "key": "year",
    "dir": "asc",
    "type": "numeric",
    "weight": 1
  },
  {
    "key": "name",
    "dir": "desc",
    "type": "alpha",
    "weight": 2
  }
]
```

set_filter(filters)
-------------------
Set filter options. See below at TableData::set_filter().

Properties
----------
`data`: The current data (an object of class TableData or similar)

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

Notes:
* additionally to the current object properties as variables, the current object is also available as variable '_'. E.g. `{{ _.name }}` equals `{{ name }}`. The advantage is, that you can apply filters to the whole object, e.g. `{{ _|json_encode }}`.

TableData
=========
__construct($data)
------------------
Creates the object. $data is list of associative arrays, as described above.

set_sort($rules)
----------
A list of sorts with their criteria (see above) (sort=>true has been expanded). The list has already been sorted by their weight.

set_sort() might be called multiple times, where each call resets the sort options.

set_filter($rules)
------------
A list of filter criteria, e.g.:
```json
[
  {
    'key': 'name',
    'op': 'contains',
    'value': 'Schubert',
  },
  {
    'key': 'year',
    'op': '<=',
    'value': '1900',
  },
]
```

set_filter() might be called multiple times, where each call resets the filter options.

Operations:

Operation | Description | Example
----------|-------------|---------
`=`       | Equal match | `{ "key": "category", "op": "=", "value", "test" }`
`>`, `>=`, `<`, `<=` | lower/greater comparison | `{ "key", "year", "op": "<", "value": "2000" }`
`regexp` | Regular expression match. Specify further options with flags: 'i' for case insenstive match, '!' for negated match (return only items NOT matching the expression). | `{ "key": "name", "op": "regexp", "value": "^A", "flags": "i" }` (list all names starting with 'A' or 'a')

count()
-------
Return the count of the filtered list.

In JS mode, require an additional parameter callback, which will be passed the result as single parameter.

get($offset=null, $limit=null)
--------------------------
Return part of the (filtered and sorted) list.

In JS mode, require an additional parameter callback, which will be passed the rsult as single parameter.

Properties
----------
* `current_sort`: the current sort options.
* `current_filter`: the current filter options.

Testdata
========
Source for the test data in data.csv is the Magistratsabteilung 31 - Wiener
Wasser; a list of historical fountains in Vienna. CC-BY-SA 3.0 AT.
Source: https://www.data.gv.at/katalog/dataset/ce6baef9-42a8-4c09-8d50-99be6ac4ca9e
