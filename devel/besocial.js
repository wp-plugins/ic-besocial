(function ($) {
	$('.be-social').each(function () {
		var entry = $(this);

        entry.data('url', entry.data('url').replace('http://local.naukas/', 'http://naukas.com/'));

		entry
			.append('<ul class="be-social-buttons"></ul>')
			.find('.be-social-buttons')
			.icBeSocial({
                wrapper: 'li',
				url: entry.data('url'),
                base: entry.data('base'),
				title: entry.data('title'),
                tracking: entry.data('track'),
                show: entry.data('show'),
				buttons: { twitter: { via: entry.data('via') }}
			});
	});
}(jQuery));