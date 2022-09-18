<?php
/**
 * Library - A fresh modren look of MediaWiki with Bootstrap framework supported.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @ingroup Skins
 */

 namespace Library;

 use OutputPage;
 use ResourceLoader;
 use ResourceLoaderContext;
 use Skin;
 use SkinTemplate;
 use SkinLibrary;

 /**
  * Presentation hook handlers for Library skin.
  *
  * Hook handler method names should be in the form of:
  * on<HookName>()
  * @package Library
  * @internal  
  */
  class Hooks {

    /**
     * called when a page has been processed by the parser and
     * resulting HTML is about to be display.
     *
     * @param OutputPage $out
     * @param string[] &$text
     * @return bool|void
     */
    public static function onOutputPageBeforeHTML( OutputPage $out, &$text ) {
        $out->addMeta( 'viewport', 'width=device-width, initial-scale=1, shrink-to-fit=no' );
        $out->addMeta( 'description', 'theLiJIA Library');
        $out->addMeta( 'author', 'theLiJIA-yongxinL');
    }

    /**
     * Called when OutputPage::headElement is creating the body tag to allow skins
     * and extensions to add attribute they might need to the body of the page.
     * 
     * @param OutputPage $out
     * @param Skin $sk
     * @param string[] &$bodyAttrs
     */
    public static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, &$bodyAttrs ) {
        if ( !$sk instanceof SkinLibrary ) {
            return;
        }
        $bodyAttrs['class'] .= '';
    }
  }