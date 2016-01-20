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

  this.current_data = opt_sort(this.orig_data, this.current_sort);
  return this.current_data;
}
