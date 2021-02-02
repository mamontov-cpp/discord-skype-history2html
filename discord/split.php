<?php
/* Script that processes output of  DiscordChatExporter.CLI, splitting it by days and writing it into
   specific folder, replacing titles in it, according to dates
   All related output and data folders must be created before running script.
 */
require_once("vendor/autoload.php");
global $discordCLIExportFile,
	   $guildIconPath,
	   $outputFolderFileTemplate,
	   $originalHeadTitle,
	   $replacedTitlePrefix,
	   $secondPreambleEntry;
// HTML file, where DiscordChatExporter.CLI export is located
$discordCLIExportFile = "";
// GUILD icon folder - better to get it from $discordCLIExportFile
// It's a relative path, that should be valid from all files, generated. You can find it from
// .preamble .preamble__guild-icon. Download it and place correctly
$guildIconPath = "";
// Here is where the files should be stored. Note that this is not final files, as 
// previews should be downloaded and postprocessed. Use discorddownload.php as second part of pipeline
$outputFolderFileTemplate = "outdiscord/orig-";
// Content of <title> tag of original page
$originalHeadTitle = "{ORIGINALHEADTITLE}";
// Prefix of <title> tag of generated pages
$replacedTitlePrefix = "{TITLE} - ";
// Content of second .preamble-entry to be replaced with date
$secondPreambleEntry = "Private / {NAME}";

// ===================== HERE WHERE THE SCRIPT STARTS =====================
set_time_limit(0);
$search  = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
$replace = array("01" , "02" , "03" , "04" , "05" , "06" , "07",  "08" , "09",  "10" , "11" , "12");

function toHtml($e)
{
	return str_replace(array("<root>", "</root>"), array("", ""), $e->toDom()->SaveHTML());
}

$html = file_get_contents($discordCLIExportFile);


$dom = new \nokogiri($html);

$head = toHtml($dom->get("head"));
$preamble = toHtml($dom->get(".preamble"));
$guildicon = $dom->get(".preamble .preamble__guild-icon")->toArray();
$guildicon  = $guildicon[0]["src"];

$dtoe = array();

$preamble = str_replace($guildicon, $guildIconPath, $preamble);


$messages = $dom->get(".chatlog__message-group")->getDom()->firstChild->childNodes;
for ($i = 0; $i < count($messages); $i++) {
	$tmp_doc = new DOMDocument();
	$tmp_doc->appendChild($tmp_doc->importNode($messages[$i], true));
	$docHtml = $tmp_doc->SaveHTML();
	$el = new \nokogiri($docHtml);
	$date = $el->get(".chatlog__timestamp")->toArray();
	$date = str_replace($search, $replace, $date[0]["#text"][0]);
	$date = explode(" ", $date);
	$date = $date[0];
	$tmp_doc = null;
	if (array_key_exists($date, $dtoe)) {
		$dtoe[$date][] = $docHtml;
	} else {
		$dtoe[$date] = array($docHtml);
	}
	$el = null;
}

foreach ($dtoe as $date => $elements) {
	$dtparts = explode("-", $date);
	$dtparts = array_reverse($dtparts);
	$dt = implode("-", $dtparts);

	$document = "<html lang=\"ru\">";
	$document .=  str_replace($originalHeadTitle, $replacedTitlePrefix . $date, $head);
	$document .= "<body>";
	$document .= str_replace($secondPreambleEntry, str_replace("-", ".", $date),  $preamble);
	$document .= "<div class=\"chatlog\">";
	foreach ($elements as $e) {
		$document .= $e;
	}
	$document .= "</div>";
	$document .= "</body>";
	$document .= "</html>";
	file_put_contents($outputFolderFileTemplate . $dt . ".html", $document);
}