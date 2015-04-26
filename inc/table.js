function table(def, data, options) {
  this.def = def;
  this.data = data;
  this.options = options;
  this.agg = {};

  if(!this.options)
    this.options = {};
  if(!this.options.template_engine)
    this.options.template_engine = "internal";
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
      return twig_render_custom(format, data);
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

  for(var k in def) {
    var v = def[k];
    var value = data[k];

    if(v.type == "multiple")
      ret = ret.concat(this.print_values(data[k], tr, v.columns));
    else {
      if(v.format)
        value = this.replace(data, tr, v.format);

      var r = { "class": k, "value": value };

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
    else
      ret.push({
        "class": k,
        "value": value
      });
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

table.prototype.print_headers = function(level, def, maxlevel) {
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
        ret = ret.concat(this.print_headers(level - 1, v.columns, maxlevel -1));
    }
    else if((v.type == "group") || (v.type == "hidden")) {
    }
    else {
      if(level == 0)
        ret.push({ "type": "head", "class": k, "rowspan": maxlevel, "value": v.name });
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

table.prototype.show = function(mode, param) {
  if(!mode)
    mode = "html";
  if(!param)
    param = {};
  var result = [];

  var has_aggregate = this.aggregate_check();

  var rows = [];
  var row = [];
  var groups = [];
  for(var l = 0; l < this.levels(); l++) {
    result.push({
      'type': 'head' + l,
      'values': this.print_headers(l)
    });
  }

  var data = this.data;
  var sorts = [];
  var has_groups = false;

  for(var k in this.def) {
    var def = this.def[k];

    if(def.sort) {
      if(def.sort === true) {
        sorts.push({
          key: k,
          type: "alpha",
          weight: 0
        });
      }
      else {
        var s = def.sort;
        s.key = k;
        sorts.push(s);
      }
    }

    if(def.type && (def.type == "group"))
      has_groups = true;
  }

  sorts = weight_sort(sorts);

  data.sort(function(sorts, a, b) {
    for(var si in sorts) {
      var s = sorts[si];
      var dir = 1;
      if(s.dir)
        dir = (s.dir == 'desc' ? -1 : 1);

      switch(s.type) {
        case 'num':
        case 'numeric':
          var av = parseFloat(a[s.key]);
          var bv = parseFloat(b[s.key]);

          if(av == bv)
            continue;

          if(isNaN(av))
            return -1;
          if(isNaN(bv))
            return 1;

          var c = (av > bv) ? 1 : -1;
          return c * dir;

        case 'nat':
          // TODO!
          break;

        case 'case':
          var av = (a[s.key] + '').toLowerCase();
          var bv = (b[s.key] + '').toLowerCase();

          if(av == bv)
            continue;

          c = (av > bv) ? 1 : -1;
          return c * dir;

        case 'alpha':
        default:
          var av = (a[s.key] + '');
          var bv = (b[s.key] + '');

          if(av == bv)
            continue;

          c = (av > bv) ? 1 : -1;
          return c * dir;
      }
    }

    return 0;
  }.bind(this, sorts));

  var count = data.length;
  if(param.limit && (param.limit <= data.length))
    count = param.limit;

  for(var rowid = 0; rowid < count; rowid++) {
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

  if(mode == "html")
    return this.print_html(result, param);
  else if(mode == "html-transposed")
    return this.print_html_transposed(result, param);
  else
    return this.print_csv(result, param);
}

table.prototype.print_html = function(result, param) {
  ret = "<table class='table'>";

  odd = false;
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

      ret += "class='"+ el['class'] +"'>";

      if(el.link)
        ret += "<a href='" + el['link'] + "'>" + el['value'] + "</a>";
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

      cols[i] += "'>";

      if(el.link)
        cols[i] += "<a href='" + el['link'] + "'>" + el['value'] + "</a>";
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
