<?
/**
 * about
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


require "inc/common_http.php";

html_head(_("About"));

readfile("about_".LANG.".html");

html_foot();
