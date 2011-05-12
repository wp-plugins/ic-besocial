var BeSocial = (function (config) {

	var url = window.location.href.replace(/(?:#.*)?$/, ''),
		head = (document.getElementsByTagName('HEAD')[0] || document.documentElement),
		network = null,
		buttons = [],
		scripts = [],
		request = {},
		response = {};

	function getNetwork(id) {
		var button = document.getElementById('besocial-' + id + '-1');
		if (button === null) {
			return false;
		} else {
			buttons[id] = button;
			return true;
		}
	}

	function cleanNetwork(id) {
		head.removeChild(scripts[id]);
		delete scripts[id];
		delete buttons[id];
	}

	function buildQuery(url, params, separator, join) {
		var query = [],
			param = null;

		separator = separator || '=';
		join = join || '&';

		for (param in params) {
			if (params[param]) {
				query.push(param.toString() + separator + encodeURIComponent(params[param]));
			}
		}

		return url + query.join(join);
	}

	function addScript(id, source) {
		scripts[id] = document.createElement('script');
		scripts[id].charSet = 'utf-8';
		scripts[id].src = source;
		scripts[id].async = true;

		head.appendChild(scripts[id]);
	}

	function addCounter(id, count) {
		var meta = document.createElement('span'),
			stat = document.createElement('span');

		meta.className = 'besocial-meta';
		meta.appendChild(document.createTextNode('\u00A0'));

		stat.className = 'besocial-stat';
		stat.appendChild(document.createTextNode(count));

		buttons[id].appendChild(meta);
		buttons[id].appendChild(stat);
		buttons[id].target = '_blank';

		cleanNetwork(id);
	}

	function parseMeneame(id, data) {
		var result = '';

		if (data.status === 'OK') {
			var item = buttons[id].parentNode,
				count = 0,
				i = 0,
				j = data.data.length;

			for (; i < j; i++) {
				if (data.data[i].status === 'published' || data.data[i].status === 'queued') {
					buttons[id].href = data.data[i].url;
					result = parseInt(data.data[i].votes, 10) + parseInt(data.data[i].anonymous, 10);
					break;
				}
			}

			if (result === '') {
				item.parentNode.removeChild(item);
			}
		} else {
			result = 0;
		}

		return result;
	}

	request = {
		facebook: function () {
			var urls = [];

			urls.push(url);

			if (config.twitter_url !== '') {
				urls.push(config.twitter_url);
			}

			addScript('facebook', buildQuery('http://api.facebook.com/restserver.php?', {
				v: '1.0',
				method: 'fql.query',
				query: 'select total_count from link_stat where url in("' + urls.join('","') + '")',
				format: 'json',
				callback: 'BeSocial.facebook'
			}));
		},

		twitter: function () {
			addScript('twitter', buildQuery('http://api.tweetmeme.com/url_info.jsonc?', {
				url: url,
				callback: 'BeSocial.twitter'
			}));
		},

		reddit: function () {
			addScript('reddit', buildQuery('http://www.reddit.com/api/info.json?', {
				url: url,
				jsonp: 'BeSocial.reddit'
			}));
		},

		buzz: function () {
			addScript('buzz', buildQuery('http://www.google.com/buzz/api/buzzThis/buzzCounter?', {
				url: url,
				callback: 'BeSocial.buzz'
			}));
		},

		delicious: function () {
			addScript('delicious', buildQuery('http://feeds.delicious.com/v2/json/urlinfo/data?', {
				url: url,
				callback: 'BeSocial.delicious'
			}));
		},

		bitacoras: function () {
			addScript('bitacoras', buildQuery('http://api.bitacoras.com/anotacion/', {
				key: config.bitacoras_apikey,
				url: url,
				format: 'json',
				callback: 'BeSocial.bitacoras'
			}, '/', '/'));
		},

		meneame: function () {
			addScript('meneame', buildQuery('http://www.meneame.net/api/url.php?', {
				url: url,
				jsonp: 'BeSocial.meneame'
			}));
		},

		divulgame: function () {
			addScript('divulgame', buildQuery('http://www.divulgame.net/api/url.php?', {
				url: url,
				jsonp: 'BeSocial.divulgame'
			}));
		},

		divoblogger: function () {
			addScript('divoblogger', buildQuery('http://divoblogger.com/api/url.php?', {
				url: url,
				jsonp: 'BeSocial.divoblogger'
			}));
		}
	};

	response = {
		facebook: function (data) {
			var count = 0,
				i = data.length - 1;

			for (; i >= 0; i--) {
				count += parseInt(data[i].total_count, 10);
			}

			addCounter('facebook', count);
		},

		twitter: function (data) {
			var count = 0;

			if (data.status === 'success') {
				count = data.story.url_count || 0;
			}

			addCounter('twitter', count);
		},

		reddit: function (data) {
			var count = 0;

			if (data.data.children.length > 0) {
				count = data.data.children[0].data.score;
			}

			addCounter('reddit', count);
		},

		buzz: function (data) {
			var count = 0;

			if (data[url] > 0) {
				count = data[url];
			}

			addCounter('buzz', count);
		},

		delicious: function (data) {
			var count = 0;

			if (data.length > 0) {
				count = data[0].total_posts;
			}

			addCounter('delicious', count);
		},

		bitacoras: function (data) {
			var count = data.data.votos || 0;
			addCounter('bitacoras', count);
		},

		meneame: function (data) {
			var count = parseMeneame('meneame', data);

			if (count !== '') {
				addCounter('meneame', count);
			}
		},

		divulgame: function (data) {
			var count = parseMeneame('divulgame', data);

			if (count !== '') {
				addCounter('divulgame', count);
			}
		},

		divoblogger: function (data) {
			var count = parseMeneame('divoblogger', data);

			if (count !== '') {
				addCounter('divoblogger', count);
			}
		}
	};

	for (network in request) {
		if (getNetwork(network)) {
			request[network]();
		}
	}

	return response;
}(BeSocial));