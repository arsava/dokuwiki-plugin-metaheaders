<?php

/**
 * DokuWiki Action Plugin MetaHeaders
 *
 *
 * LICENSE: This file is open source software (OSS) and may be copied under
 *          certain conditions. See COPYING file for details or try to contact
 *          the author(s) of this file in doubt.
 *
 * @license GPLv2 (http://www.gnu.org/licenses/gpl2.html)
 * @author ARSAVA <dokuwiki@dev.arsava.com>
 * @author Michael Klier <chi@chimeric.de> (creator and previous maintainer)
 * @link https://www.dokuwiki.org/plugin:metaheaders
 * @link https://www.dokuwiki.org/devel:plugins
 * @link https://www.dokuwiki.org/devel:coding_style
 * @link https://www.dokuwiki.org/devel:environment
 */


//check if we are running within the DokuWiki environment
if (!defined("DOKU_INC")){
    die();
}


if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');


/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class.
 */
class action_plugin_metaheaders extends DokuWiki_Action_Plugin {

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaheaders');
    }

    /**
     * Modifies the meta headers before their send to the browser.
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function metaheaders(Doku_Event $event, $param) {
        global $ID;
        global $INFO;
        global $ACT;
        global $clear;
        global $headers;

        if ($ACT != 'show' || !page_exists($ID)) return;

        $head =& $event->data;

        $headerconf = DOKU_CONF.'metaheaders.conf.php';

        if (@file_exists($headerconf) && is_array($clear)) {

            require_once($headerconf);
            $nclear = count($clear);

            if (!empty($clear)) {

                foreach( $head as $outerType => $list ) {
					
                    $nlink = count($list);
                    // process link tags
                    for ($i = 0; $i < $nlink; $i++) {
                        for ($y = 0; $y < $nclear; $y++) {
                            if ($clear[$y]['cond']) {
		                        if (!preg_match('/' . $clear[$y]['cond'] . '/', $ID)) {
		                            continue;
		                        }
		                    }
		                    
		                    $unset = true;
		                    foreach ($clear[$y] as $type => $value) {
		                        if ($type == 'cond') continue;
		                        
		                        $headerVal = trim($head[$outerType][$i][$type]);
		                        if ( substr($type, 0, 1) == '%' ) {
			                        $type = substr($type, 1 );
									$headerVal = trim($head[$outerType][$i][$type]);
			                        if ( !preg_match(trim($value), $headerVal ) ) {
				                        $unset = false;
				                    }
		                        } else 
		                        if ($headerVal != trim($value)) {
			                        $unset = false;
								}
		                    }
		                    if ($unset) {
		                        unset($head[$outerType][$i]);
		                    }
		                }
		            }
                }
            }
        }

        $replace = array('@AUTHOR@'       => $INFO['meta']['creator'],
                         '@ID@'           => $INFO['id'],
                         '@CREATED@'      => date('Y-m-d\TH:i:sO',$INFO['meta']['date']['created']),
                         '@LASTMOD@'      => date('Y-m-d\TH:i:sO',$INFO['lastmod']),
                         '@ABSTRACT@'     => preg_replace("/\s+/", ' ', $INFO['meta']['description']['abstract']),
                         '@TITLE@'        => $INFO['meta']['title'],
                         '@RELATION@'     => @implode(', ', @array_keys($INFO['meta']['relation']['references'])),
                         '@CONTRIBUTORS@' => @implode(', ', @array_values($INFO['meta']['contributor']))
                         );

        // apply new headers skip if conditions aren't met or header value is empty
        if (!empty($headers)) {
            $types = array_keys($headers);
            foreach ($types as $type) {
                foreach ($headers[$type] as $header) {
                    $skip = false;

                    if ($header['cond']) {
                        if (preg_match('/'.$header['cond'].'/', $ID)) {
                            unset($header['cond']);
                        } else{
                            $skip = true;
                        }
                    }

                    foreach ($header as $attr => $value) {
                        $value = str_replace(array_keys($replace), array_values($replace), $value);
                        if (empty($value)) {
                            $skip = true;
                        }else{
                            $header[$attr] = $value;
                        }
                    }

                    if (!$skip) $head[$type][] = $header;

                }
            }
        }

        return true;
    }
}

