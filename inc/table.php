<?
class table {
  function __construct($conf, $options=array()) {
    $this->conf = $conf;
    $this->options = $options;
  }

  function set_data($data) {
    $this->data = $data;
  }

  function show() {
    global $mustache;

    $ret  = "<table class='table'>\n";

    foreach($this->conf as $k=>$v) {
      if(!is_array($v))
	$this->conf[$k] = array("name"=>$v, "format"=>"{{{$k}}}");
    }

    $ret .= "  <tr>\n";
    foreach($this->conf as $k=>$v) {
      $k_order_dir = ($k == $options['order'] ?
        ($options['order_dir']=="asc" ? "desc" : "asc") :
	"desc");

      $ret.="    <th class='$k'><a href='.?order=$k&order_dir=$k_order_dir'>{$v['name']}</a></th>\n";
    }
    $ret .= "  </tr>\n";


    foreach($this->data as $entry) {
      $ret .= "<tr>\n";

      foreach($this->conf as $k=>$def) {
	$d = $mustache->render("{{%FILTERS}}" . $def['format'], $entry);
	$class="$k";

	$ret .= "<td class='{$class}'>$d</td>\n";
      }
      $ret .= "</tr>\n";
    }

    $ret .= "</table>\n";

    return $ret;
  }
}

register_hook("init", function() {
  global $mustache;
  require "lib/mustache.php/src/Mustache/Autoloader.php";

  Mustache_Autoloader::register();
  $mustache = new Mustache_Engine;
});
