/*! jQuery JSON plugin v2.6.0 | github.com/Krinkle/jquery-json */
!(function (a) {
	"function" == typeof define && define.amd
		? define(["jquery"], a)
		: a("object" == typeof exports ? require("jquery") : jQuery);
})(function ($) {
	"use strict";
	var escape = /["\\\x00-\x1f\x7f-\x9f]/g,
		meta = {
			"\b": "\\b",
			"\t": "\\t",
			"\n": "\\n",
			"\f": "\\f",
			"\r": "\\r",
			'"': '\\"',
			"\\": "\\\\",
		},
		hasOwn = Object.prototype.hasOwnProperty;
	($.toJSON =
		"object" == typeof JSON && JSON.stringify
			? JSON.stringify
			: function (a) {
					if (null === a) return "null";
					var b,
						c,
						d,
						e,
						f = $.type(a);
					if ("undefined" !== f) {
						if ("number" === f || "boolean" === f) return String(a);
						if ("string" === f) return $.quoteString(a);
						if ("function" == typeof a.toJSON) return $.toJSON(a.toJSON());
						if ("date" === f) {
							var g = a.getUTCMonth() + 1,
								h = a.getUTCDate(),
								i = a.getUTCFullYear(),
								j = a.getUTCHours(),
								k = a.getUTCMinutes(),
								l = a.getUTCSeconds(),
								m = a.getUTCMilliseconds();
							return (
								g < 10 && (g = "0" + g),
								h < 10 && (h = "0" + h),
								j < 10 && (j = "0" + j),
								k < 10 && (k = "0" + k),
								l < 10 && (l = "0" + l),
								m < 100 && (m = "0" + m),
								m < 10 && (m = "0" + m),
								'"' +
									i +
									"-" +
									g +
									"-" +
									h +
									"T" +
									j +
									":" +
									k +
									":" +
									l +
									"." +
									m +
									'Z"'
							);
						}
						if (((b = []), $.isArray(a))) {
							for (c = 0; c < a.length; c++) b.push($.toJSON(a[c]) || "null");
							return "[" + b.join(",") + "]";
						}
						if ("object" == typeof a) {
							for (c in a)
								if (hasOwn.call(a, c)) {
									if (((f = typeof c), "number" === f)) d = '"' + c + '"';
									else {
										if ("string" !== f) continue;
										d = $.quoteString(c);
									}
									(f = typeof a[c]),
										"function" !== f &&
											"undefined" !== f &&
											((e = $.toJSON(a[c])), b.push(d + ":" + e));
								}
							return "{" + b.join(",") + "}";
						}
					}
			  }),
		($.evalJSON =
			"object" == typeof JSON && JSON.parse
				? JSON.parse
				: function (str) {
						return eval("(" + str + ")");
				  }),
		($.secureEvalJSON =
			"object" == typeof JSON && JSON.parse
				? JSON.parse
				: function (str) {
						var filtered = str
							.replace(/\\["\\\/bfnrtu]/g, "@")
							.replace(
								/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,
								"]"
							)
							.replace(/(?:^|:|,)(?:\s*\[)+/g, "");
						if (/^[\],:{}\s]*$/.test(filtered)) return eval("(" + str + ")");
						throw new SyntaxError("Error parsing JSON, source is not valid.");
				  }),
		($.quoteString = function (a) {
			return a.match(escape)
				? '"' +
						a.replace(escape, function (a) {
							var b = meta[a];
							return "string" == typeof b
								? b
								: ((b = a.charCodeAt()),
								  "\\u00" +
										Math.floor(b / 16).toString(16) +
										(b % 16).toString(16));
						}) +
						'"'
				: '"' + a + '"';
		});
});
