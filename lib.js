var L = (function() {
	function $(id) {
		if (typeof id != "string") return id;
		if (id.substr(0, 1) == "#") id = id.substr(1);
		return document.getElementById(id);
	}

	function camelize(str) { return str.replace(/-+(.)?/g, function(match, chr){ return chr ? chr.toUpperCase() : ''; }); }

	// lgpl: http://www.dustindiaz.com/rock-solid-addevent/
	function addEvent(obj, type, cb) {
		function fn(e) {
			if (e.srcElement) e.target = e.srcElement;
			var u = cb.apply(e.target, [e]);
			if (u === "non-target") return true;
			if (!u) {
				e.preventDefault();
				if (e.stopPropagation) e.stopPropagation(); else e.cancelBubble = true;
			}
			return u || false;
		}
		if (obj.addEventListener) {
			obj.addEventListener(type, fn, false);
			EventCache.add(obj, type, fn);
		}
		else if (obj.attachEvent) {
			obj["e"+type+fn] = fn;
			obj[type+fn] = function() { obj["e"+type+fn]( window.event ); };
			obj.attachEvent( "on"+type, obj[type+fn] );
			EventCache.add(obj, type, fn);
		}
		else {
			obj["on"+type] = obj["e"+type+fn];
		}
	}

	var EventCache = function(){
		var listEvents = [];
		return {
			listEvents : listEvents,
			add: function(node, sEventName, fHandler){
				if (node != window && !node.evId) node.evId = listEvents.length;
				listEvents.push(arguments);
			},
			flush: function(){
				var i, item;
				for (i = listEvents.length - 1; i >= 0; i = i - 1){
					item = listEvents[i];
					if (item[0].removeEventListener){
						item[0].removeEventListener(item[1], item[2], typeof item[3] != "undefined" ? item[3] : null);
					}
					if (item[1].substring(0, 2) != "on"){
						item[1] = "on" + item[1];
					}
					if (item[0].detachEvent){
						item[0].detachEvent(item[1], item[2]);
					}
					item[0][item[1]] = null;
				}
			},
			trigger: function(el, type){
				var i, item, evId = el.evId;
				if (!evId) return;
				for(i = listEvents.length - 1; i >= 0; i = i - 1){
					item = listEvents[i];
					if (item[1] == type && item[0].evId === evId) {
						item[2]({
							target: item[0],
							preventDefault: function() {}
						});
					}
				}
			}
		};
	}();
	addEvent(window, 'unload', EventCache.flush);

	$.on = function(el, type, fn) {
		if (!(el = $(el))) return false;
		return addEvent(el, type, fn);
	};
	$.live = function(root, what, type, cb) {
		if (!(root = $(root))) return false;

		if (what.substr(0, 1) == ".") {
			var className = what.substr(1);
			return addEvent(root, type, function(e) {
				if (!$.hasClass(e.target, className)) return "non-target";
				return cb.apply(e.target, [e]);
			});
		}

		if (what.substr(0, 1) == "#") {
			var id = what.substr(1);
			return addEvent(root, type, function(e) {
				if (e.target.id == id) return "non-target";
				return cb.apply(e.target, [e]);
			});
		}

		var nodeName = what.toUpperCase();
		return addEvent(root, type, function(e) {
			if (e.target.nodeName != nodeName) return "non-target";
			return cb.apply(e.target, [e]);
		});
	};
	$.extend = function(target, source) {
		for (var key in source) target[key] = source[key];
		return target;
	};
	$.callable = function(value) {
		return ({}).toString.call(value) == "[object Function]";
	};
	$.trigger = function(el, type) {
		if (!(el = $(el))) return false;
		return EventCache.trigger(el, type);
	};
	$.stripTags = function(html) {
		return html.replace(/(<([^>]+)>)/ig, "");
	};
	$.ready = function(fn) {
		return addEvent(document, "DOMContentLoaded", fn);
	};
	$.css = function(el, css) {
		if (!(el = $(el))) return false;
		for (var i in css) {
			el.style[camelize(i)] = css[i];
		}
		return true;
	};
	$.visible = function(el) {
		if (!(el = $(el))) return false;
		return el.offsetWidth || el.offsetHeight;
	};
	$.visibleFocus = function(el) {
		if (!(el = $(el))) return false;
		if ($.visible(el)) el.focus();
		return true;
	};
	$.closest = function(el, what, className) {
		if (!(el = $(el))) return false;
		if (what.substr(0, 1) == ".") {
			className = what.substr(1);
			what = "";
		}
		if (what === "" && typeof className != "undefined") {
			while ((el = el.parentNode) !== null) {
				if ($.hasClass(el, className)) break;
			}
		} else {
			nodeName = what.toUpperCase();
			while ((el = el.parentNode) !== null) {
				if (el.nodeName == nodeName) {
					if (typeof className != "undefined") {
						if ($.hasClass(el, className)) break;
					} else break;
				}
			}
		}
		return el;
	};
	$.find = function(el, what, className) {
		if (!(el = $(el))) return false;
		if (typeof el.getElementsByTagName == "undefined") return null;
		var els = el.getElementsByTagName(what);
		if (typeof className == "undefined") return els[0];

		for (var i = 0, l = els.length; i < l; i++) {
			if ($.hasClass(els[i], className)) return els[i];
		}
		return null;
	};

	$.scrollTo = function(el) {
		if (!(el = $(el))) return false;
		el.scrollIntoView(true);
	};

	$.trim = function(str) {
		return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
	};

	$.hasClass = function(el, classRegex) {
		if (!(el = $(el))) return false;
		var c = new RegExp("\\b(" + classRegex + ")\\b");
		return c.test(el.className);
	};

	$.addClass = function(el, className) {
		if (!(el = $(el))) return false;
		if ($.hasClass(el, className)) return el.className;
		return el.className += " " + className;
	};

	$.removeClass = function(el, className) {
		if (!(el = $(el))) return false;
		if (!$.hasClass(el, className)) return el.className;
		var c = new RegExp("\\b(" + className + ")\\b");
		return el.className = L.trim(el.className.replace(c, ""));
	};

	function convertParams(map) {
		var pairs = [], backstop = {}, assign;
		for (var name in map) {
			var value = map[name];
			if (value == backstop[name]) continue;
			if (value instanceof Array || typeof value == "array") {
				assign = encodeURIComponent(name) + "[]=";
				for (var i = 0; i < value.length; i++){
					pairs.push(assign + encodeURIComponent(value[i]));
				}
			} else {
				pairs.push(encodeURIComponent(name) + "=" + encodeURIComponent(value));
			}
		}
		return pairs.join("&");
	}

	var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
	$.xhr = function(url, params, callback, errorCallback) {
		xhr.onreadystatechange = function() {
			if (this.readyState != 4) return false;
			if (this.status != 200) {
				if (typeof errorCallback == "function") return errorCallback(this.responseText);
				return false;
			}
			var ct = this.getResponseHeader('content-type');
			if (ct.indexOf("javascript") >= 0 || ct.indexOf("json") >= 0) {
				callback(typeof JSON != "undefined" ? JSON.parse(this.responseText) : eval("(" + this.responseText + ")"));
			} else {
				callback(this.responseText);
			}
			return true;
		};
		var requestType = "POST";
		params = typeof params == "string" ? params : convertParams(params);
		if (typeof url.href != "undefined") {
			requestType = "GET";
			url = url.href + (/\?/.test(url) ? "&" : "?") + params;
			params = null;
		}
		xhr.open(requestType, url, true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		xhr.send(params);
	};

	return $;
})();
