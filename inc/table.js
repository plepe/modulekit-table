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
        ret.push({ "class": k, "colspan": cols, "value": v.name });
      }
      else
        ret = ret.concat(this.print_headers(level - 1, v.columns, maxlevel -1));
    }
    else if((v.type == "group") || (v.type == "hidden")) {
    }
    else {
      if(level == 0)
        ret.push({ "class": k, "rowspan": maxlevel, "value": v.name });
      else
        ret.push(null);
    }
  }

  return ret;
}

table.prototype.print_row = function(elem, mode) {
  switch(mode) {
    case "html":
      var r = elem.value;

      if(elem.link)
        var r = "<a href='" + elem.link + "'>" + r + "</a>";

      return "    <td class='" + elem['class'] + "'>" + r + "</td>\n";
    case "csv":
      return elem.value;
  }
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
  var ret = "";
  var csv_conf;

  var has_aggregate = this.aggregate_check();

  switch(mode) {
    case "html":
      ret = "<table class='table'>";
      break;
    case "csv":
      if(param != null)
        csv_conf = param;
      else
        csv_conf = [",", "\"", "UTF-8"];
      ret = "";
      break;
    default:
      alert("Table: invalid mode '" + mode + "'");
  }

  var rows = [];
  var row = [];
  var groups = [];
  for(var l = 0; l < this.levels(); l++) {
    switch(mode) {
      case "html":
        ret += "  <tr>\n";
        break;
    }

    var headers = this.print_headers(l);
    for(var h = 0; h < headers.length; h++) {
      var elem = headers[h];

      switch(mode) {
        case "html":
          if(elem != null) {
            ret += "<th class='" + elem['class'] + "'";
            if(elem.colspan)
              ret += " colspan='" + elem.colspan + "'";
            if(elem.rowspan)
              ret += " rowspan='" + elem.colspan + "'";
            ret += ">" + elem.value + "</th>\n";
          }
          break;
        case "csv":
          var colspan = 1;
          if(elem.colspan)
            colspan = elem.colspan;

          for(var i = 0; i < colspan; i++) {
            if(elem != null)
              row.push(elem.value);
            else
              row.push("");
          }
      }
    }

    switch(mode) {
      case "html":
        ret += "  </tr>\n";
        break;
      case "csv":
        ret += printcsv(row, csv_conf[0], csv_conf[1]);
        row = [];
        break;
    }
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
        row.push(this.print_row(elem, mode));
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
      switch(mode) {
        case "html":
          ret += "  <tr class='group'>\n";
          ret += "    <td colspan='" + group_rows[0].length + "'>" + group_value + "</td>\n";
          ret += "  </tr>\n";
          break;
        case "csv":
          ret += printcsv([group_value], csv_conf[0], csv_conf[1]);
          break;
      }
    }

    for(var rowid = 0; rowid < group_rows.length; rowid++) {
      var row = group_rows[rowid];

      switch(mode) {
        case "html":
          ret += "  <tr class='" + (odd ? "odd" : "even") + "'>\n";
          ret += row.join("\n");
          ret += "  </tr>\n";
          row = [];
          break;
        case "csv":
	  ret += printcsv(row, csv_conf[0], csv_conf[1]);
	  row = [];
	  break;
      }

      odd = !odd;
    }
  }

  if(has_aggregate) {
    var aggs = this.print_aggregate(agg);
    for(var i = 0; i < aggs.length; i++) {
      var elem = aggs[i];

      row.push(this.print_row(elem, mode));
    }

    switch(mode) {
      case "html":
        ret += "  <tr class='agg'>\n";
        ret += row.join("\n");
        ret += "  </tr>\n";
        row = [];
        break;
      case "csv":
        ret += printcsv(row, csv_conf[0], csv_conf[1]);
        row = [];
        break;
    }
  }

  switch(mode) {
    case "html":
      ret += "</table>\n";
      break;
  }

  return ret;
}
