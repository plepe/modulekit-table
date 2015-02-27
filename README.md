```php
$table = new table($definition, $data, [$options]);
print $table->show([$mode, [$param]])
```

The following options are available:
Key             | Description | Possible values (highlight = default)
----------------|-------------|---------------------------------------
template_engine | Which templating engine to use | **internal**, twig[1]

[1] If you want to use [Twig](http://twig.sensiolabs.org/) as templating engine, include the module 'twig' of [modulekit-base](https://github.com/plepe/modulekit-base). The example format from below would like this: "<a href='foo.html?id={{ id }}'>{{ name }}</a>".

```php
$def = column definition, array:
"column_id"=>array(
  "name"=>
  "format"=> (optional), define format of field using other values as 
                         replacement patterns in [ ]
                         e.g. "<a href='foo.html?id=[id]'>[name]</a>"

$data = table data, array:
"row_id"=>array(
   "column_id"=>array("foobar")
)
```
