function TableData(data) {
  this.orig_data = data;

  this.current_sort = [];
  this.current_filter = [];
  this.current_data = null;
}

TableData.prototype.set_sort = function(rules) {
  this.current_sort = rules;
  this.current_data = null;
}

TableData.prototype.set_filter = function(rules) {
  this.current_filter = rules;
  this.current_data = null;
}

TableData.prototype.count = function(callback) {
  this.run();

  callback(this.current_data.length);
}

TableData.prototype.get = function(offset, limit, callback) {
  this.run();

  if(offset === null)
    offset = 0;

  if(limit === null)
    callback(this.current_data.slice(offset));
  else
    callback(this.current_data.slice(offset, limit));
}

TableData.prototype.run = function() {
  if(this.current_data)
    return;

  this.current_data = this.orig_data.slice();

  this.current_data.sort(function(sorts, a, b) {
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
  }.bind(this, this.current_sort));
}
