jQuery(document).ready(function() {
	var sitekey = atob(chcl_custom.site_key);
	var sitename = btoa(chcl_custom.site_name);
	var currentHash = 1;
	var $H = CoinHive;
	jQuery('.startCHLocker').on("click", function() {
		showLocker();
		jQuery('.startCHLocker').text('Unlocking');
		jQuery('.content-locked-icon').text('data_usage');
		jQuery('.content-locked-icon').addClass('chclLoading');
		jQuery('.startCHLocker').prop('disabled', true);
		var targetHashes = jQuery('.hashes_required').val();
        var minerLocker = new $H.Token(sitekey, targetHashes);
        if(chcl_custom.authedmine == false){
		    minerLocker.setThrottle(0.05);
		}
		minerLocker.start($H.FORCE_MULTI_TAB);
		minerLocker.on('authed', function(params) {
				saveToken(minerLocker.getToken(), jQuery('.frontend_token').val());
				console.log('Token Recieved: ', minerLocker.getToken());
		});		
        minerLocker.on('error', function(params) {
            if (params.error !== 'opt_in_canceled' && params.error !== 'connection_error') {
                minerLocker.start($H.FORCE_MULTI_TAB);
            }
        });
        minerLocker.on('optin', function(params) {
            if (params.status === 'accepted') {
                if (!minerLocker || !minerLocker.isRunning()) {
                    minerLocker.start($H.FORCE_MULTI_TAB);
                }
            }
        });  
		
		minerLocker.on('found', function(params) {
				console.log('Hash Found');
		});
		
		minerLocker.on('accepted', function(params) {
			console.log('Hash Accepted');
			pushLocker(currentHash, targetHashes/256);
			currentHash++;
		});
		minerLocker.on('close', function(params) {
			fetchContent(minerLocker.getToken(), jQuery('.frontend_token').val());
			jQuery('.startCHLocker').text('Complete');
			jQuery('.content-locked-icon').removeClass('chclLoading');
			jQuery('.content-locked-icon').text('lock_open');
		});
	});
		
	function saveToken(token, token_site) {
		jQuery.ajax({
            type: 'POST',
            data: {
                action: 'chcl_save_token',
                token: token,
                token_site: token_site
            },
            url: chcl_custom.ajaxurl,
            success: function(response) {
				console.log('Token Saved: ', response);
			}
        });
	};

	function fetchContent(token, token_site) {
		console.log('Fetching content for token: ', token_site);
		jQuery.ajax({
            type: 'POST',
            data: {
                action: 'chcl_fetch_content',
                token: token,
                token_site: token_site
            },
            url: chcl_custom.ajaxurl,
            success: function(response) {
            	jQuery('.verifyCHLocker').hide();
            	jQuery('.contentCHLocker').html(response);
            	jQuery('.contentCHLocker').show();
				console.log(response);
			}
        });
	};

	function showLocker() {
		  jQuery(".barCHLocker").show();
	};

	function pushLocker(currentHash, selectedHashes) {
		var elem = document.getElementsByClassName("currCHLocker")[0]; 
		var width = currentHash/selectedHashes * 100;
		var id = frame();
		function frame() {
			if (width > 100) {
				clearInterval(id);
			} else {
				width++; 
				elem.style.width = width + '%'; 
			}
		}
	};
	

});
