/**
 * AI Chat Assistant — Admin JS.
 *
 * - "Test connection" button (Jokko AI Cloud)
 * - Bubble icon media uploader
 * - Display rules show/hide
 * - "Clear conversations" confirmation
 */
( function () {
    'use strict';

    // ── Test Cloud connection ────────────────────────────────────────────────
    var testBtn    = document.getElementById( 'waicb-test-cloud' );
    var testResult = document.getElementById( 'waicb-test-cloud-result' );

    if ( testBtn && testResult ) {
        testBtn.addEventListener( 'click', function () {
            testResult.textContent = waicbAdmin.i18n.testing;
            testResult.className    = '';
            testBtn.disabled        = true;

            var formData = new FormData();
            formData.append( 'action', 'waicb_test_api' );
            formData.append( 'nonce', waicbAdmin.nonce );
            formData.append( 'provider', 'cloud' );

            // Send the key from the field if it has been modified (not the masked placeholder).
            var keyField = document.getElementById( 'waicb_cloud_key' );
            var keyValue = keyField ? keyField.value : '';
            if ( keyValue && keyValue.indexOf( '•' ) === -1 && keyValue.indexOf( '****' ) === -1 ) {
                formData.append( 'api_key', keyValue );
            }

            fetch( waicbAdmin.ajaxUrl, {
                method:      'POST',
                credentials: 'same-origin',
                body:        formData,
            } )
            .then( function ( res ) { return res.json(); } )
            .then( function ( data ) {
                testBtn.disabled = false;
                if ( data.success ) {
                    testResult.textContent = data.data.message;
                    testResult.className   = 'success';
                } else {
                    testResult.textContent = '✗ ' + data.data.message;
                    testResult.className   = 'error';
                }
            } )
            .catch( function ( err ) {
                testBtn.disabled       = false;
                testResult.textContent = '✗ ' + err.message;
                testResult.className   = 'error';
            } );
        } );
    }

    // ── Bubble icon media uploader ───────────────────────────────────────────
    var uploadBtn   = document.getElementById( 'waicb-upload-icon' );
    var removeBtn   = document.getElementById( 'waicb-remove-icon' );
    var iconInput   = document.getElementById( 'waicb_bubble_icon' );
    var iconPreview = document.getElementById( 'waicb-icon-preview' );

    if ( uploadBtn && typeof wp !== 'undefined' && wp.media ) {
        var mediaFrame;

        uploadBtn.addEventListener( 'click', function ( e ) {
            e.preventDefault();

            if ( mediaFrame ) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media( {
                title:    'Choisir l\'icône de la bulle',
                button:   { text: 'Utiliser cette image' },
                multiple: false,
                library:  { type: 'image' },
            } );

            mediaFrame.on( 'select', function () {
                var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
                var url = attachment.url;

                iconInput.value = url;
                iconPreview.querySelector( 'img' ).src = url;
                iconPreview.style.display = '';
                removeBtn.style.display   = '';
            } );

            mediaFrame.open();
        } );

        if ( removeBtn ) {
            removeBtn.addEventListener( 'click', function () {
                iconInput.value           = '';
                iconPreview.style.display = 'none';
                removeBtn.style.display   = 'none';
            } );
        }
    }

    // ── Display rules: show/hide page list ───────────────────────────────────
    var displayRadios   = document.querySelectorAll( 'input[name="waicb_display_mode"]' );
    var displayPagesRow = document.getElementById( 'waicb-display-pages-row' );

    if ( displayRadios.length && displayPagesRow ) {
        displayRadios.forEach( function ( radio ) {
            radio.addEventListener( 'change', function () {
                displayPagesRow.style.display = this.value === 'all' ? 'none' : '';
            } );
        } );
    }

    // ── Clear conversations confirmation ─────────────────────────────────────
    var clearForm = document.getElementById( 'waicb-clear-form' );

    if ( clearForm ) {
        clearForm.addEventListener( 'submit', function ( e ) {
            if ( ! window.confirm( waicbAdmin.i18n.confirmClear ) ) {
                e.preventDefault();
            }
        } );
    }

}() );
