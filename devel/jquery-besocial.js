(function ($, window, document) {
	var
		pluginName = 'icBeSocial',
		className = 'be-social',

		defaults = {
			show: {
                google: true,
                facebook: true,
                twitter: true,
                reddit: true,
                linkedin: true,
                meneame: true
			},
			title: document.title,
			url: document.location.href,
            base: '/wp-content/plugins/ic-besocial',
			tracking: true, //tracking with Google Analitycs
            wrapper: 'div',
			buttons: {
				google: {  //http://www.google.com/webmasters/+1/button/
					lang: 'es',
					size: 'standard'
				},
				facebook: { //http://developers.facebook.com/docs/reference/plugins/like/
					label: 'Compartir en Facebook',
                    title: 'Se ha compartido {count} veces.',
					url: '',
					width: 850,
					height: 500
				},
				twitter: {  //http://twitter.com/about/resources/tweetbutton
					label: 'Twittear',
					title: '{count} personas han twitteado esto.',
					url: '',
					via: '',
					width: 650,
					height: 360
				},
                reddit: {
                    label: 'Enviar a Reddit',
                    title: 'Puntuación {count}.',
                    url: '',
                    width: 550,
                    height: 550
                },
                linkedin: {
                    label: 'Compartir en LinkedIn',
                    title: 'Se ha compartido {count} veces.',
                    url: '',
                    width: 550,
                    height: 550
                },
				meneame: {
					label: 'Menear',
					title: '{count} personas han meneado esto.',
					url: '',
					ok: 'Entrada pendiente de aprobarse.',
					ko: 'No se aceptó la entrada.',
					width: 850,
					height: 500
				}
			}
		},

		selector = function (name, prefix) {
			prefix = prefix || '';
			return prefix + className + '-' + name;
		},

		json = {
			//facebook: 'http://graph.facebook.com/?id={url}&callback=?',
            facebook: 'https://graph.facebook.com/fql?q=select%20%20total_count%20from%20link_stat%20where%20url="{url}"&callback=?',
			twitter: 'http://cdn.api.twitter.com/1/urls/count.json?url={url}&callback=?',
            reddit: '{base}/reddit.php?url={url}',
            linkedin: 'http://www.linkedin.com/countserv/count/share?format=jsonp&url={url}&callback=?',
			meneame: 'http://www.meneame.net/api/url.php?url={url}&jsonp=?'
		},

		share = {
			facebook: function (options) {
				return 'http://www.facebook.com/sharer.php?u=' + encodeURIComponent(options.url) + '&t=' + options.title;
			},
			twitter: function (options) {
				return 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(options.title) + '&url=' + encodeURIComponent(options.url) + (options.buttons.twitter.via !== '' ? '&via=' + options.buttons.twitter.via : '');
			},
            reddit: function (options) {
                return 'http://www.reddit.com/submit?url=' + encodeURIComponent(options.url) + '&title=' + encodeURIComponent(options.title);
            },
            linkedin: function (options) {
                return 'https://www.linkedin.com/cws/share?url=' + encodeURIComponent(options.url) + '&token=&isFramed=true';
            },
			meneame: function (options) {
				return (options.buttons.meneame.url !== '') ? options.buttons.meneame.url : 'http://www.meneame.net/submit.php?url=' + encodeURIComponent(options.url) + '&title=' + encodeURIComponent(options.title);
			}
		},

		tracking = {
			facebook: { site: 'Facebook', action: 'Like' },
			twitter: { site: 'Twitter', action: 'Tweet' },
            reddit: { site: 'Reddit', action: 'Share' },
            linkedin: { site: 'LinkedIn', action: 'Share' },
			meneame: { site: 'Meneame', action: 'Meneo' }
		},

        plusone = {
            loading: false,

            script: function (lang) {
                if (plusone.loading === false) {
                    plusone.loading = true;

                    window.___gcfg = {
                        lang: lang
                    };

                    $('head').append('<script type="text/javascript" async="async" src="https://apis.google.com/js/plusone.js">');
                }
            }
        },

        meneame = function (json, options) {
            var count = 0;

            if (json.status === 'OK') {
                var data	= json.data,
                    result	= '',
                    pendent	= false,
                    i		= 0,
                    j		= data.length;

                for (; i < j; i++) {
                    options.url = data[i].url;

                    if (data[i].status === 'published' || data[i].status === 'queued') {
                        result = parseInt(data[i].votes, 10) + parseInt(data[i].anonymous, 10);
                        break;
                    } else if (data[i].status === 'pendent') {
                        pendent = true;
                        break;
                    }
                }

                if (result === '') {
                    if (pendent) {
                        options.title = options.ok;
                        count = ':-)';
                    } else {
                        options.title = options.ko;
                        count = ':-(';
                    }
                } else {
                    count = result;
                }
            }

            return count;
        },

        popup = function (options) {
			window.open(options.url, '', 'toolbar=0, status=0, scrollbars=1, width=' + options.width + ', height=' + options.height);
		};

	function Plugin(element, options) {
		this.element = $(element);
		this.options = $.extend(true, {}, defaults, options);

		this.init();
	}

	Plugin.prototype.init = function () {
		var self = this;

		$.each(this.options.show, function (name, show) {
			if (show === true) {

				self.element.prepend('<' + self.options.wrapper + ' class="' + selector('button') + ' ' + selector(name) + '"></' + self.options.wrapper + '>');

				if (name === 'google') {
					self.plusone();
				} else {
					try {
						self.count(name);
					} catch (e) {
					}
				}
			}
		});
	};

	Plugin.prototype.plusone = function () {
		var options = this.options.buttons.google;

		this.element
			.find(selector('google', '.'))
			.html(
				'<div class="g-plusone" data-size="' + options.size + '" data-href="' + this.options.url + '"></div>'
			);

		plusone.script(options.lang);
	};

	Plugin.prototype.count = function (name) {
        var self  = this,
            count = 0,
            url   = json[name].replace('{url}', this.options.url).replace('{base}', this.options.base);

        $.getJSON(url, function (json) {
            switch (name) {
                case 'twitter':
                    if (typeof json.count !== 'undefined') {
                        count = parseInt(json.count, 10);
                    }
                    break;
                case 'facebook':
                    if (typeof json.data[0].total_count !== 'undefined') { // FQL
                        count = parseInt(json.data[0].total_count, 10);
                    } else if (typeof json.shares !== 'undefined') { // id
                        count = parseInt(json.shares, 10);
                    }
                    break;
                case 'reddit':
                    if (json.data.children.length > 0) {
                        for (var i = 0, j = json.data.children.length; i < j; i++) {
                            count += parseInt(json.data.children[i].data.score, 10);
                        }
                    }
                    break;
                case 'linkedin':
                    if (typeof json.count !== 'undefined') {
                        count = parseInt(json.count, 10);
                    }
                    break;
                case 'meneame':
                    count = meneame(json, self.options.buttons.meneame);
                    break;
            }

			self.render(name, count);
		});
	};

	Plugin.prototype.render = function (name, count) {
		var self	= this,
			options	= this.options.buttons[name],
			title	= options.title;

		options.url = share[name](this.options);

		if (typeof count === 'number') {
			title = title.replace('{count}', count);
			count = this.shorter(count);
		}

		this.element
			.find(selector(name, '.'))
			.html(
				'<a class="' + selector('link') + '" href="' + options.url + '" title="' + options.label + '">' + options.label + '</a>' +
				' <span class="' + selector('count') + '" title="' + title + '">' + count + '</span>'
			).on({
				click: function () {
					return self.popup(name);
				}
			});
	};

	Plugin.prototype.shorter = function (num) {
		if (num >= 1e6) {
			num = (num / 1e6).toFixed(2) + "M";
		} else if (num >= 1e3) { 
			num = (num / 1e3).toFixed(1) + "k";
		}

		return num;
	};

	Plugin.prototype.popup = function (name) {
		popup(this.options.buttons[name]);

		if ((this.options.tracking === true) && (typeof _gaq !== 'undefined')) {
			_gaq.push(['_trackSocial', tracking[name].site, tracking[name].action]);
		}

		return false;
	};

	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, 'plugin_' + pluginName)) {
				$.data(this, 'plugin_' + pluginName, new Plugin(this, options));
			}
		});
	};

}(jQuery, window, document));