$ = @jQuery
settings = @lowtone_contact_form_7_woocommerce_products

$ -> 
	$('.wpcf7-woocommerce_products').each ->
		$input = $ this

		$form = $input.closest 'form'

		_wpcf7 = $form.find('input[name="_wpcf7"]').val()
		_wpnonce = $form.find('input[name="_wpnonce"]').val()

		options = 
			type: 'GET'
			url: settings.ajaxurl
			dataType: 'json'
			jsonTermKey: 's'
			data:
				action: 'lowtone_contact_form_7_woocommerce_products'
				'_wpcf7': _wpcf7
				'_wpnonce': _wpnonce
				keepTypingMsg: settings.keepTypingMsg
				lookingForMsg: settings.lookingForMsg
		
		$input.ajaxChosen options

		$form.parent().on 'mailsent.wpcf7', ->
			$input
				.empty()
				.trigger('chosen:updated')