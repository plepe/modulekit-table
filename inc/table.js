function table(def, data, options) {
  this.def = def;
  this.options = options;
  if(!this.options)
    this.options = {};
  if(!("id" in this.options))
    this.options.id = (Math.random() + "").replace("0.", "t");
  this.id = this.options.id;
  this.agg = {};

  if((typeof data == "undefined") || (data === null))
    this.data = new TableData([]);
  else if(data.length)
    this.data = new TableData(data);
  else
    this.data = data;

  for(var k in this.def) {
    if(!('type' in this.def[k]))
      this.def[k].type = 'default';
  }

  if(!this.options)
    this.options = {};
  if(!this.options.template_engine)
    this.options.template_engine = "internal";

  this.options.base_url = location.search

  window.setTimeout(this.connect.bind(this), 1);
  this.show_column_style = document.createElement("style");
  document.head.appendChild(this.show_column_style);
  window.addEventListener("resize", this.resize.bind(this));
}

table.prototype.connect = function() {
  this.table = document.getElementById(this.id);
  this.resize();
}

table.prototype.url = function (add_params = {}) {
  let addr = {}

  if (location.search.substr(0, 1) === '?') {
    addr = page_resolve_url_params(location.search)
  }

  for (let k in add_params) {
    addr[k] = add_params[k]
  }

  return page_url(addr)
}

table.prototype.resize = function() {
  if(!this.table)
    return;

  this.show_column_style.innerHTML =  "";
  for(var k in this.def) {
    this.def[k].is_hidden = false;
  }

  var max_width = this.table.parentNode.offsetWidth;

  while(this.table.offsetWidth > max_width) {
    var lowest_priority = 9999999999;
    var lowest_column = null;

    for(var k in this.def) {
      if(this.def[k].show_priority && (!this.def[k].is_hidden) && (this.def[k].show_priority < lowest_priority)) {
        lowest_priority = this.def[k].show_priority;
        lowest_column = k;
      }
    }

    if(!lowest_column)
      return;

    this.show_column_style.innerHTML +=
      "table#" + this.id + " td." + lowest_column + " { display: none; }\n" +
      "table#" + this.id + " th." + lowest_column + " { display: none; }";
    this.def[lowest_column].is_hidden = true;
  }
}

table.prototype.columns = function(def) {
  if(!def)
    def = this.def;

  var columns = 0;

  for(var k in def) {
    var v = def[k];

    switch(v.type) {
      case "multiple":
        columns += this.columns(v.columns);
        break;
      case "group":
      case "hidden":
        break;
      default:
        columns ++;
    }
  }

  return columns;
}

table.prototype.levels = function(def) {
  if(!def)
    def = this.def;

  var sub_levels = 0;

  for(var k in def) {
    var v = def[k];

    switch(v.type) {
      case "multiple":
        var l = this.levels(v.columns);
        if(l > sub_levels)
          sub_levels = l;
        break;
    }
  }

  return sub_levels + 1;
}

table.prototype.replace = function(data, tr, format) {
  switch(this.options.template_engine) {
    case "twig":
      try {
        return twig_render_custom(format, data);
      }
      catch (e) {
        return '#ERROR#'
      }
    case "internal":
    default:
      var result = format;
      for(var k in tr)
        result = result.replace(k, tr[k]);
      return result;
  }
}

table.prototype.print_values = function(data, tr, def) {
  var ret = [];
  if(!def)
    def = this.def;

  if(typeof data.view == "function") {
    data = data.view();
  }

  // clone data to make sure, that data does not get changed by functions
  data = JSON.parse(JSON.stringify(data));

  for(var k in def) {
    var v = def[k];
    var value = data[k];

    if(v.type == "multiple")
      ret = ret.concat(this.print_values(data[k], tr, v.columns));
    else {
      if(v.format)
        value = this.replace(data, tr, v.format);

      var _class = k
      if (v.class) {
        _class += ' ' + this.replace(data, tr, v.class);
      }

      var r = { "class": _class, "value": value };

      if(v.html_attributes)
        r.html_attributes = this.replace(data, tr, v.html_attributes);

      if(v.type)
        r.type = v.type;

      if(v.link)
        r.link = this.replace(data, tr, v.link);

      ret.push(r);
    }
  }

  return ret;
}

table.prototype.print_aggregate = function(agg, def) {
  var ret = [];
  if(!def)
    dev = this.def;

  for(var k in def) {
    var v = def[k];
    var value = agg[k];

    if(v.type == "multiple")
      ret = ret.concat(this.print_aggregate(agg[k], v.columns));
    else {
      var r = {
        "class": k,
        "value": value
      };

      if(v.html_attributes)
        r.html_attributes = v.html_attributes;

      ret.push(r);
    }
  }

  return ret;
}

table.prototype.aggregate_check = function(def) {
  if(!def)
    def = this.def;

  for(var k in def) {
    var v = def[k];

    if(v.type == "multiple") {
      var ret = this.aggregate_check(v.columns);
      if(ret)
        return true;
    }
    else {
      if(v.agg)
        return true;
    }
  }

  return false;
}

table.prototype.aggregate_values = function(data, agg, def) {
  if(!def)
    def = this.def;

  for(var k in def) {
    var v = def[k];
    var value = data[k];

    if(v.type == "multiple")
      this.aggregate_values(data[k], agg[k], v.columns);
    else {
      if(v.agg) switch(v.agg) {
        case "count_values":
        if((k in data) && (data[k] !== null))
          agg[k]++;
      }
    }
  }
}

table.prototype.print_headers = function(level, def, maxlevel, param) {
  if(maxlevel === null)
    maxlevel = this.levels(def);
  if(!def)
    def = this.def;
  var ret = [];

  for(var k in def) {
    var v = def[k];

    if(v.type == "multiple") {
      if(level == 0) {
        var cols = this.columns(v.columns);
        ret.push({ "type": "head", "class": k, "colspan": cols, "value": v.name });
      }
      else
        ret = ret.concat(this.print_headers(level - 1, v.columns, maxlevel -1, param));
    }
    else if((v.type == "group") || (v.type == "hidden")) {
    }
    else {
      if(level == 0) {
        var r = { "type": "head", "class": k, "rowspan": maxlevel, "value": v.name };

        if(v.html_attributes)
          r.html_attributes = v.html_attributes;

        let append_url = {};
        if (v.sort || v.sortable) {
          let s = v.sort || v.sortable;

          append_url.sort = k;

          if (param.sort && param.sort === k) {
            if (param.sort_dir) {
              append_url.sort_dir = param.sort_dir === 'asc' ? 'desc' : 'asc';
              r.value += param.sort_dir === 'asc' ? ' ▲' : ' ▼';
            } else {
              append_url.sort_dir = !('dir' in s) || s.dir == 'asc' ? 'desc' : 'asc';
              r.value += param.sort_dir === 'asc' ? ' ▲' : ' ▼';
            }
          } else {
            append_url.sort_dir = s.dir && s.dir == 'desc' ? 'desc' : 'asc';
          }
        }

        if (Object.values(append_url).length) {
          r.link = this.url(append_url);
        }

        ret.push(r);
      }
      else
        ret.push(null);
    }
  }

  return ret;
}

table.prototype.build_tr = function(rowv, prefix) {
  if(!prefix)
    prefix = "";

  switch(this.options.template_engine) {
    case "twig":
      break;
    case "internal":
    default:
      var tr = {};

      for(var k in rowv) {
        var v = rowv[k];

        if(typeof v == "object")
          tr = tr.concat(this.build_tr(v, prefix + k + "."));
        else
          tr["[" + prefix + k + "]"] = v;
      }
  }

  return tr;
}

table.prototype.show = function(mode, param, callback) {
  if(typeof mode == "function") {
    param = mode;
    mode = "html";
  }

  if(typeof param == "function") {
    callback = param;
    param = {}
  }

  if(typeof callback != "function") {
    alert("table::show() requires callback function!");
    return;
  }

  if(!mode)
    mode = "html";
  if(!param)
    param = {};
  var result = [];

  var groups = [];
  for(var l = 0; l < this.levels(); l++) {
    result.push({
      'type': 'head' + l,
      'values': this.print_headers(l, null, null, param)
    });
  }

  var sorts = [];
  var has_groups = false;

  for(var k in this.def) {
    var def = this.def[k];
    var s = null

    if(def.sort) {
      if(def.sort === true) {
        s = {
          key: k,
          type: "alpha",
          weight: 0
        }
      }
      else if (def.sort === false) {
        // nothing
      }
      else {
        s = def.sort;
        s.key = k;
        sorts.push(s);
      }
    }

    if (def.sortable && param.sort && param.sort === k) {
      if (def.sortable === true) {
        s = {
          key: k,
          type: 'alpha',
          weight: 0
        }
      }
      else {
        s = JSON.parse(JSON.stringify(def.sortable))
        s.key = k
      }
    }

    if (param.sort && param.sort === k) {
      s.weight = -10000

      if (param.sort_dir) {
        s.dir = param.sort_dir
      }
    }

    if (s) {
      sorts.push(s)
    }

    if(def.type && (def.type == "group"))
      has_groups = true;
  }

  sorts = weight_sort(sorts);

  this.data.set_sort(sorts);

  var offset = param.offset ? param.offset : null;
  var limit = param.limit ? param.limit : null;

  return this.data.get(offset, limit, this.show1.bind(this, mode, param, callback, result, groups, has_groups));
}

table.prototype.show1 = function(mode, param, callback, result, groups, has_groups, data) {
  var rows = [];
  var row = [];
  console.log(data);

  var has_aggregate = this.aggregate_check();

  for(var rowid = 0; rowid < data.length; rowid++) {
    var rowv = data[rowid];
    var tr = this.build_tr(rowv);

    if(has_aggregate)
      this.aggregate_values(rowv, agg);

    var group = [];
    var group_value = [];
    var row = [];

    var values = this.print_values(rowv, tr);
    for(var i = 0; i < values.length; i++) {
      var elem = values[i];

      if(elem.type && ((elem.type == "group") || (elem.type == "hidden"))) {
        if(elem.type == "group") {
          group.push(elem);
          group_value.push(elem.value);
        }
      }
      else {
        row.push(elem);
      }
    }

    group_value = group_value.join("|");
    groups[group_value] = group;
    if(!(group_value in rows))
      rows[group_value] = [];
    rows[group_value].push(row);
  }

  var odd = false;
  for(var group_value in rows) {
    var group_rows = rows[group_value];

    if(has_groups) {
      result.push({
        'type': 'group',
        'values': [
          {
            'value': group_value,
            'colspan': group_rows[0].length
          }
        ]
      });
    }

    for(var rowid = 0; rowid < group_rows.length; rowid++) {
      result.push({
        'type': 'element',
        'values': group_rows[rowid]
      });
    }
  }

  if(has_aggregate) {
    result.push({
      'type': 'agg',
      'values': this.print_aggregate(agg)
    });
  }

  var ret;
  if(mode == "html")
    ret = this.print_html(result, param);
  else if(mode == "html-transposed")
    ret = this.print_html_transposed(result, param);
  else
    ret = this.print_csv(result, param);

  callback(ret);
}

table.prototype.print_html = function(result, param) {
  var ret = "<table class='table' id='" + this.id + "'>";

  var odd = false;
  for(var rowid = 0; rowid < result.length; rowid++) {
    var row = result[rowid];

    switch(row['type']) {
      case "element":
        ret += "  <tr class='"+ (odd ? "odd" : "even") +"'>\n";
        break;
      default:
        ret += "  <tr class='"+ row['type'] +"'>\n";
    }

    for(var elid = 0; elid < row['values'].length; elid++) {
      var el = row['values'][elid];

      if(el.type && (el['type'] == 'head')) {
        ret += "    <th ";
        var end = "</th>";
      }
      else {
        ret += "    <td ";
        var end = "</td>";
      }

      if(el.colspan)
        ret += "colspan='"+ el['colspan'] +"' ";
      if(el.rowspan)
        ret += "rowspan='"+ el['rowspan'] +"' ";

      ret += "class='"+ el['class'] +"'";

      if(el.html_attributes)
        ret += el.html_attributes + " ";

      ret += ">";

      if(el.link)
        ret += "<a class='table_link' href='" + el['link'] + "'>" + el['value'] + "</a>";
      else
        ret += el['value'];

      ret += end;

    }

    ret += "  </tr>\n";
    if(row['type'] == "element")
      odd = !odd;
  }

  ret += "</table>\n";
  return ret;
}

table.prototype.print_html_transposed = function(result, param) {
  var ret = "<table class='table transposed'>";
  var cols = [];

  var odd = "even";
  for(var rowid = 0; rowid < result.length; rowid++) {
    var row = result[rowid];
    var i = 0;

    for(var elid = 0; elid < row['values'].length; elid++) {
      if(!cols[i])
        cols[i] = "";

      var el = row['values'][elid];

      if(el.type && (el['type'] == 'head')) {
        cols[i] += "    <th ";
        var end = "</th>";
      }
      else {
        cols[i] += "    <td ";
        var end = "</td>";
      }

      if(el.colspan)
        cols[i] += "rowspan='" + el['colspan'] + "' ";
      if(el.rowspan)
        cols[i] += "colspan='" + el['rowspan'] + "' ";

      cols[i] += "class='" + el['class'];

      switch(row['type']) {
        case "element":
          cols[i] += " " + odd;
          break;
        default:
          cols[i] += " " + row['type'];
      }

      cols[i] += "' ";

      if(el.html_attributes)
        cols[i] += el.html_attributes + " ";

      cols[i] += ">";

      if(el.link)
        cols[i] += "<a class='table_link' href='" + el['link'] + "'>" + el['value'] + "</a>";
      else
        cols[i] += el['value'];

      cols[i] += end + "\n";

      i++;
    }

    if(row['type'] == "element")
      odd = (odd == "even" ? "odd": "even");
  }

  for(var i = 0; i < cols.length; i++) {
    ret += "  <tr>\n" + cols[i] + "  </tr>\n";
  }

  ret += "</table>\n";
  return ret;
}

table.prototype.print_csv = function(result, param) {
  ret = "";

  if(param != null)
    csv_conf = param;
  else
    csv_conf = [ ",", "\"", "UTF-8" ];

  for(var rowid = 0; rowid < result.length; rowid++) {
    var row = result[rowid];

    to_print = [];

    for(var elid = 0; elid < row['values'].length; elid++) {
      var el = row['values'][elid];

      colspan = 1;
      if(el.colspan)
        colspan = el['colspan'];

      for(i = 0; i < colspan; i++)
        to_print.push(el['value']);
    }

    ret += printcsv(to_print, csv_conf[0], csv_conf[1]);
  }

  return ret;
}
