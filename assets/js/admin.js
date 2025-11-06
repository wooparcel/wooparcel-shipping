/**
 * WooParcel Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Ensure submit reaches backend using AJAX
        $('#wooparcel-settings-form').on('submit', function(e) {
            try {
                var $form = $(this);
                var key = $.trim($('#api_key').val() || '');
                var code = $.trim($('#api_code').val() || '');
                var remoteApi = $.trim($('#remote_api').val() || '');
                var autoAwb = $('#auto_awb').is(':checked');
                console.log('[WooParcel] Submitting settings', {
                    api_key_len: key.length,
                    api_code_len: code.length,
                    remote_api_len: remoteApi.length,
                    auto_awb: autoAwb
                });
                e.preventDefault();
                var ajaxUrl = window.WooParcelAjax && WooParcelAjax.ajaxUrl ? WooParcelAjax.ajaxUrl : (window.ajaxurl || '/wp-admin/admin-ajax.php');
                var nonce   = window.WooParcelAjax && WooParcelAjax.nonce ? WooParcelAjax.nonce : $('input[name="wooparcel_settings_nonce"]').val();
                if (!ajaxUrl) {
                    console.error('[WooParcel] Missing AJAX URL; aborting save');
                    return false;
                }

                $.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'wooparcel_save_settings',
                        wooparcel_settings_nonce: nonce,
                        api_key: key,
                        api_code: code,
                        remote_api: remoteApi,
                        auto_awb: autoAwb ? 'on' : ''
                    }
                }).done(function(resp) {
                    console.log('[WooParcel] AJAX save response', resp);
                    if (resp && resp.success && window.WooParcelAjax && WooParcelAjax.redirect) {
                        window.location = WooParcelAjax.redirect;
                    } else {
                        alert(resp && resp.data && resp.data.message ? resp.data.message : 'Save failed.');
                    }
                }).fail(function(xhr) {
                    console.log('[WooParcel] AJAX save failed', xhr && xhr.responseText);
                    alert('Save failed. Check debug log for details.');
                });
            } catch (err) {}
        });
        // Marker that script loaded
        if (window.console && window.console.log) {
            console.log('[WooParcel] admin.js loaded');
        }

        // Also log on Save button click to catch fast navigations
        $('button[name="wooparcel_save_settings"]').on('click', function(e) {
            console.log('[WooParcel] Click Save');
            try {
                var key = $.trim($('#api_key').val() || '');
                var code = $.trim($('#api_code').val() || '');
                var autoAwb = $('#auto_awb').is(':checked');
                console.log('[WooParcel] Click Save', {
                    api_key_len: key.length,
                    api_code_len: code.length,
                    auto_awb: autoAwb
                });
                // Ensure form submit handler runs for AJAX path
                e.preventDefault();
                $('#wooparcel-settings-form').trigger('submit');
            } catch (err) {
                console.log('[WooParcel] Error clicking Save', err);
                console.log('[WooParcel] Error clicking Save', {
                    api_key_len: key.length,
                    api_code_len: code.length,
                    auto_awb: autoAwb
                });
            }
        });
        
        // Animate tab switching
        $('.nav-tab').on('click', function(e) {
            // Smooth transition
            $('.wooparcel-content').fadeOut(200, function() {
                $(this).fadeIn(200);
            });
        });
        
        // Toggle switch animation
        $('.wooparcel-toggle input[type="checkbox"]').on('change', function() {
            const $toggle = $(this);
            const isChecked = $toggle.is(':checked');
            
            // Add a visual feedback
            $toggle.closest('.wooparcel-toggle').addClass('toggle-active');
            
            setTimeout(function() {
                $toggle.closest('.wooparcel-toggle').removeClass('toggle-active');
            }, 200);
            
            // Log for debugging
            if (window.console && window.console.log) {
                console.log('Auto AWB toggle:', isChecked);
            }
        });
        
        // Auto-dismiss success notice
        setTimeout(function() {
            $('.notice-success.is-dismissible').fadeOut('slow');
        }, 5000);
        
        // Add loading state to save button
        $('button[name="wooparcel_save_settings"]').on('click', function() {
            const $button = $(this);
            const originalText = $button.text();
            
            $button.prop('disabled', true)
                   .text('Saving...')
                   .css('opacity', '0.6');
            
            // Re-enable after a delay (the form will submit)
            setTimeout(function() {
                $button.prop('disabled', false)
                       .text(originalText)
                       .css('opacity', '1');
            }, 1000);
        });

        // Handle AWB label download
        $(document).on('click', '.wooparcel-download-label', function(e) {
            e.preventDefault();
            const $button = $(this);
            const labelBase64 = $button.data('label');
            const awbCode = $button.data('awb') || 'label';
            
            if (!labelBase64) {
                alert('Label not available.');
                return;
            }
            
            try {
                // Convert base64 to blob
                const byteCharacters = atob(labelBase64);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                const blob = new Blob([byteArray], { type: 'application/pdf' });
                
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = awbCode + '.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                // Visual feedback
                $button.text('Downloaded!').prop('disabled', true);
                setTimeout(function() {
                    $button.text('Download Label').prop('disabled', false);
                }, 2000);
            } catch (err) {
                console.error('[WooParcel] Error downloading label:', err);
                alert('Failed to download label. Please try again.');
            }
        });
        
    });

})(jQuery);

