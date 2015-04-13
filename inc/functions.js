function printcsv(row, delim, encl) {
  if(!delim)
    delim = ",";
  if(!encl)
    encl = "\"";

  var l = [];

  for(var i = 0; i < row.length; i++) {
    var r = row[i];

    r.replace("&shy;", "");
    r.replace("\"", "\\\"");

    if((typeof r == "number") || (r.match(/^[0-9]+(\.[0-9]+)?([eE]\-?[0-9]+)?/)))
      l.push(r);
    else
      l.push(encl + r + encl);
  }

  return l.join(delim) + "\r\n";
}

