<?php
/*! Simple script, that makes a simple list for menu.html to create
    required lis item for left frame. Please, paste generated items into <ul> in menu.html. 
    Use first item to set as link in CONTENT frame in index.html.
    index.html and menu.html should be placed with the output files
 */
$array = array(
"out-18-11-07.html",
"out-18-11-08.html",
"out-18-11-09.html",
... // PLEASE PLACE HERE  YOUR OWN FILES, use dir /b out-* to form such list
);

// ===================== HERE WHERE THE SCRIPT STARTS =====================
foreach($array as $item) {
	$s = str_replace(array("out-", ".html"), array("", ""), $item);
	$dtparts = explode("-", $s);
	$dtparts = array_reverse($dtparts);
	$dt = implode(".", $dtparts);
	echo "<li><a target=\"CONTENT\" href=\"$item\">$dt</a></li>\n";
}
