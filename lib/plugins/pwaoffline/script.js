jQuery(document).ready(function($){
  if(!navigator.onLine) {
    jQuery(function () {
      showMessage('You are offline. Some functionality may be unavailable.','error', true);
    });

    // Replace PDF.JS embed
    var pdfembed = document.getElementById("pdf-embed");
    if(pdfembed != null) {
      var file = document.getElementsByTagName('iframe')[0].src;
      var pointer = file.indexOf("=");
      file = decodeURIComponent(file.substr(pointer+1));
      if (file != null ){
        pdfembed.innerHTML = '<button type="button" class="btn btn-default"><a href="'+file+'" >You are offline. Click to Open.</a></button>';
      }
    }
  }
});

if ('serviceWorker' in navigator) {

    jQuery(function () {
        showMessage('Service Worker active!', 'success');
    });
    const serviceWorkerScript = DOKU_BASE + 'doku.php?do=pwaoffline_serviceworker';
    navigator.serviceWorker
        .register(serviceWorkerScript, {
                scope: '.'
            }
        )
        .then(function (registration) {
                if (registration.active) {
                    registration.active.postMessage({
                        type: 'getLastUpdate',
                    });
                    registration.active.postMessage({
                        type: 'getHashVersion',
                    });
                }
            }
        )
    ;

    navigator.serviceWorker.addEventListener('message', function swMessageListener(event){
        console.log('[Main Script] received message: ', event.data);
        switch(event.data.type) {
            case 'lastUpdate':
                jQuery.get(DOKU_BASE + 'lib/exe/ajax.php', {
                    call: 'plugin_pwaoffline',
                    ts: event.data.ts,
                }).done(function (data) {
                    navigator.serviceWorker.controller.postMessage({
                        type: 'updatePages',
                        pages: data,
                    });
                });
                break;
            case 'swHashVersion':
                if (event.data.hash !== JSINFO.plugins.pwaoffline.swHashVersion) {
                    showMessage(
                        `You are using an outdated serviceWorker! active: ${event.data.hash} current: ${JSINFO.plugins.pwaoffline.swHashVersion}`,
                        'notify'
                    );
                    return;
                }
                showMessage('ServiceWorker is up-to-date!', 'success');
        }
    });

} else {
    jQuery(function () {
        showMessage('Service Worker not supported!', 'error');
    });
}

function reportStorageUsage() {

    /**
     * @param {int} size the size in byte
     * @returns {string} The size in mebibyte with appended unit
     */
    function getAsStringMiB(size) {
        const CONVERSION_FACTOR = 1024;
        return Math.round(size/(CONVERSION_FACTOR * CONVERSION_FACTOR) * 10) / 10 + ' MiB';
    }

    if (!navigator.storage) {
        showMessage('Storage API is not available?', 'notify');
        return;
    }

    navigator.storage.estimate().then(estimate => {
        const perc = Math.round((estimate.usage / estimate.quota) * 100 * 100) / 100;
        const severity = perc > 80 ? 'error' : perc > 20 ? 'notify' : 'info';
        const usage = getAsStringMiB(estimate.usage);
        const quota = getAsStringMiB(estimate.quota);
        const msg = 'Current storage usage on this device for this origin: ' + usage + '/' + quota;
        showMessage(msg + ' ( ' + perc + ' % )', severity);
    });
}

function showMessage(message, severity, display = false) {
    if(display) {
      let $msgArea = jQuery('div.pwaOfflineMSGArea');
      if (!$msgArea.length) {
          $msgArea = jQuery('<div>').addClass('pwaOfflineMSGArea');
          jQuery('#dokuwiki__header').after($msgArea);
      }
      $msgArea.append(jQuery('<div>')
          .text(message)
          .addClass(severity)
      );
  }
}

jQuery(function () {

    const LIVE_DELAY = 10;
    const now = Math.floor(Date.now() / 1000);

    const lag = now - JSINFO.plugins.pwaoffline.ts;

    if (lag > LIVE_DELAY) {
        showMessage('This page may have been loaded from cache. Age in seconds: ' + lag, 'notify');
        jQuery('.dokuwiki').addClass('pwa--is-offline');
    }

    reportStorageUsage();

// if (!navigator.onLine) {
//     jQuery('<div></div>').text('You appear to be offline')
// }

});
