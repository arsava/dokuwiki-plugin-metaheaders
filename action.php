<?php
/**
 * DokuWiki Action Plugin MetaHeaders
 * 
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Michael Klier <chi@chimeric.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class action_plugin_metaheaders extends DokuWiki_Action_Plugin {

    function getInfo() {
        return array(
                'author' => 'Michael Klier',
                'email' => 'chi@chimeric.de',
                'date' => '2008-07-25',
                'name' => 'metaheaders',
                'desc' => 'Lets you add/remove meta headers.',
                'url' => 'http://wiki.splitbrain.org/plugin:metaheaders'
            );
    }

    function register(&$controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaheaders');
    }

    /**
     * Modifies the meta headers before their send to the browser.
     *
     * Michael Klier <chi@chimeric.de>
     */
    function metaheaders(&$event, $param) {
        global $ID;
        global $INFO;
        global $ACT;

        if($ACT != 'show' || !page_exists($ID)) return;

        $head =& $event->data;

        $headerconf = DOKU_CONF . 'metaheaders.conf.php';

        if(@file_exists($headerconf)) {

            require_once($headerconf);
            $nlink = count($head['link']);
            $nmeta = count($head['meta']);
            $nclear = count($clear);

            if(!empty($clear)) {
                // process link tags
                for($i = 0; $i < $nlink; $i++) {
                    for($y = 0; $y < $nclear; $y++) {
                        if($clear[$y]['cond']) {
                            if(!preg_match('/' . $clear[$y]['cond'] . '/', $ID)) {
                                continue;
                            }
                        }
                        $unset = true;
                        foreach($clear[$y] as $type => $value) {
                            if($type == 'cond') continue;
                            if(trim($head['link'][$i][$type]) != trim($value)) $unset = false;
                        }
                        if($unset) {
                            unset($head['link'][$i]);
                        }
                    }
                }
                // process meta tags
                for($i = 0; $i < $nmeta; $i++) {
                    for($y = 0; $y < $nclear; $y++) {
                        if($clear[$y]['cond']) {
                            if(!preg_match('/' . $clear[$y]['cond'] . '/', $ID)) {
                                continue;
                            }
                        }
                        $unset = true;
                        foreach($clear[$y] as $type => $value) {
                            if($type == 'cond') continue;
                            if(trim($head['meta'][$i][$type]) != trim($value)) $unset = false;
                        }
                        if($unset) {
                            unset($head['meta'][$i]);
                        }
                    }
                }
            }
        }

        $replace = array( '@AUTHOR@' => $INFO['meta']['creator'],
                          '@ID@' => $INFO['id'],
                          '@CREATED@' => $INFO['meta']['date']['created'],
                          '@LASTMOD@' => $INFO['lastmod'],
                          '@ABSTRACT@' => preg_replace("/\s+/", ' ', $INFO['meta']['description']['abstract']),
                          '@TITLE@' => $INFO['meta']['title'],
                          '@RELATION@' => @implode(', ', @array_keys($INFO['meta']['relation']['references'])),
                          '@CONTRIBUTORS@' => @implode(', ', @array_values($INFO['meta']['contributor']))
                        );

        // apply new headers skip if conditions aren't met or header value is empty
        if(!empty($headers)) {
            $types = array_keys($headers);
            foreach($types as $type) {
                foreach($headers[$type] as $header) {
                    $skip = false;

                    if($header['cond']) {
                        if(preg_match('/' . $header['cond'] . '/', $ID)) {
                            unset($header['cond']);
                        } else{
                            $skip = true;
                        }
                    }

                    foreach($header as $attr => $value) {
                        $value = str_replace(array_keys($replace), array_values($replace), $value);
                        if(empty($value)) {
                            $skip = true;
                        } else {
                            $header[$attr] = $value;
                        }
                    }

                    if(!$skip) $head[$type][] = $header;

                }
            }
        }

        return true;
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
