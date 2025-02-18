<?php

class action_plugin_pwaoffline extends DokuWiki_Action_Plugin
{

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     *
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('MANIFEST_SEND', 'BEFORE', $this, 'add144pxImageToManifest');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'collectPagesToCache');
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'writeConfigToJSINFO');

    }

    /**
     * [Custom event handler which performs action]
     *
     * Event: MANIFEST_SEND
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     *
     * @return void
     */
    public function add144pxImageToManifest(Doku_Event $event, $param)
    {
        $event->data['icons'][] = [
            // 'src' => DOKU_BASE . 'lib/plugins/pwaoffline/144.png',
            'src' => DOKU_BASE . '_media/wiki/apple-touch-icon.png',
            'sizes' => '144x144',
        ];
    }

    /**
     * Event: AJAX_CALL_UNKNOWN
     *
     * @param Doku_Event $event
     * @param            $param
     */
    public function collectPagesToCache(Doku_Event $event, $param)
    {
        if ($event->data !== 'plugin_pwaoffline') {
            return;
        }

        global $conf, $INPUT;

        // fixme do a full resync if the config was saved?
        $ts = $INPUT->has('ts') ? $INPUT->int('ts') : 0;


        // Get Pages
        search($pages, $conf['datadir'], 'search_allpages', ['skipacl' => false]);
        //echo "<script>console.log(".json_encode($pages).")</script>";
        $pagesToCache = [];
        foreach ($pages as $pageData) {
            if ($pageData['mtime'] < $ts) {
                continue;
            }
            if( substr( $pageData['id'], 0, 4 ) === "wiki" || substr( $pageData['id'], 0, 10 ) === "playground" || substr( $pageData['id'], 0, 7 ) === "sidebar" ) {
                continue;
            }
            $pagesToCache[] = [
                'link' => wl($pageData['id']),
                'lastmod' => $pageData['mtime'],
            ];
        }

        // Get Media Files to cache
        search($media, $conf['mediadir'], 'search_media', ['skipacl' => true,'depth' => 0]);
        foreach ($media as $mediaData) {
            if ($mediaData['mtime'] < $ts) {
                continue;
            }
            $pagesToCache[] = [
                'link' => ml($mediaData['id']),
                'lastmod' => $mediaData['mtime'],
            ];
        }


        // Get Extra files to cache
        $paths = [
          'lib/plugins/pdfjs/pdfjs/build/',
          'lib/plugins/pdfjs/pdfjs/web/locale/',

        ];
        foreach ($paths as $path) {
          $files = array_diff(scandir('../../'.$path), array('.', '..'));
          // var_dump($files);
          // filemtime($base.'/'.$file)
          // $pagesExtra = [];
          foreach ($files as $file) {
            $file_time = filemtime('../../'.$path.$file);
            if ($file_time < $ts) {
                continue;
            }
            if(!is_dir('../../'.$path.$file)){
            $pagesToCache[] = [
              'link' => DOKU_BASE.$path.$file,
              'lastmod' => $file_time,
            ];
          }
          }
        }

        header('Content-Type:application/json');
        echo json_encode($pagesToCache);

        $event->preventDefault();
        $event->stopPropagation();
    }

    /**
     * Event: DOKUWIKI_STARTED
     *
     * @param Doku_Event $event
     * @param            $param
     */
    public function writeConfigToJSINFO(Doku_Event $event, $param)
    {
        global $ACT;
        if (act_clean($ACT) === 'pwaoffline_serviceworker') {
            header('Content-Type:application/javascript');
            $swjs = file_get_contents(__DIR__ . '/sw.js');
            echo $swjs;
            echo "const swHashVersion = '" . md5($swjs) . "';\n";
            $idbKeyVal = file_get_contents(__DIR__ . '/node_modules/idb-keyval/dist/idb-keyval-iife.min.js');
            echo $idbKeyVal;
            exit();
        }

        global $JSINFO;
        header('X-DWPLUGIN-PWAOFFLINE-ACT:' . act_clean($ACT));
        if (empty($JSINFO['plugins'])) {
            $JSINFO['plugins'] = [];
        }

        $JSINFO['plugins']['pwaoffline'] = [
            'ts' => time(),
            'swHashVersion' => md5(file_get_contents(__DIR__ . '/sw.js')),
        ];
    }

}
