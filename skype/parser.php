<?php
/* Script that processes source Skype's main.db (see to get the guide: https://coderoad.ru/44502283/skype-%D0%BA%D0%B0%D0%BA-%D1%87%D0%B8%D1%82%D0%B0%D1%82%D1%8C-%D1%81%D0%BE%D0%BE%D0%B1%D1%89%D0%B5%D0%BD%D0%B8%D0%B5-%D1%87%D0%B0%D1%82%D0%B0-%D1%81-%D0%BF%D0%BE%D0%BC%D0%BE%D1%89%D1%8C%D1%8E-main-db and find id of converstation) and outputs it as html, using DiscordChatExporter.CLI styles to create css. 
   All related output and data folders must be created before running script.
   Note, that you must download avatar images and set those paths before running script.
 */
require_once("vendor/autoload.php");
use Dusterio\LinkPreview\Client;
set_time_limit(0);
global $dbFileName,
	   $conversationId,
	   $outputFolder,
	   $outputDestinationFolder,
	   $outputFilePrefix;

// File path to Skype's database
$dbFileName = "main.db";
// Id of conversation to be saved (use SQLite viewer of $dbFileName to get the one dialog you want to save)
$conversationId = 209;
// Output folder, where the files will be stored
$outputFolder = "outskype";
// A destination folder, placed in $outputFolder, which previews will be saved to
$destinationPreviewFolder  = "data";
// Title name that will be used in title tag of pages
$headTitleName = "{TITLENAME}";
// Output file prefix, used, when writing final html
$outputFilePrefix = "out-";
// Path to default avatar
$defaultAvatar = "data/ava1.png";
// Avatar path mapping maps avatars from displayed name to their image counterparts, that should be downloaded and placed in folder, accessible from destination files to be replaced in dialog.
// Note, that those are taken by name, not by online image path
$avatarPathMapping = array(
	"John" => "data/ava2.png"
);

function startsWith( $haystack, $needle ) {
     $length = strlen( $needle );
     return substr( $haystack, 0, $length ) === $needle;
}


function baseExtension($file) {
	$f = explode("/", $file);
	$f = $f[count($f) - 1];
	$f = explode(".", $file);
	$f = $f[count($f) - 1];
	$f = explode(":", $f);
	$f = $f[0];
	$f = explode("?", $f);
	$f = $f[0];
	$f = str_replace("/", "", $f);
	return $f;
}


function try_handle_file(&$counter, $file, $dt) {
	global $outputFolder, $destinationPreviewFolder;
	$ext = baseExtension($file);
	echo "Trying to handle file " . $file . "\n";
	if (mb_strlen($ext) >= 3 && mb_strlen($ext) <= 4) {
		echo ("Downloading - ");
		$outlink = "$destinationPreviewFolder/$dt-$counter.$ext";
		$outfile = "$outputFolder/$outlink";
		$fp = fopen ($outfile, 'w+');
		$ch = curl_init($file);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_FILE, $fp); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_exec($ch);
		$result = $outlink;
		if (mb_strlen(curl_error($ch)) == 0) {
			echo "Downloaded!\n";
			$counter = $counter + 1;
		} else {
			echo "Error!\n";
			$result = $file;
		}
		curl_close($ch);
		fclose($fp);
		return $result;
	} else {
		return $file;
	}
}


$error_message = '';
$db = new PDO('sqlite:' . $dbFileName);
$db->setAttribute(PDO::ATTR_ERRMODE,  PDO::ERRMODE_EXCEPTION);
$query =  "SELECT `id`, `timestamp`, `from_dispname`, `body_xml`  FROM Messages WHERE `convo_id` = {$conversationId} ORDER BY `timestamp` ASC; ";
$dtoe = array();
foreach ($db->query($query) as $row) {
	$fileformatted = date('Y-m-d', $row['timestamp']);
	$dateformatted = date("d.m.Y", $row['timestamp']);
	if (!array_key_exists($fileformatted, $dtoe)) {
		echo $dateformatted . "\n";
		$dtoe[$fileformatted] = array(
			"date" => $dateformatted,
			"messages" => array( $row )
		);
	} else {
		$dtoe[$fileformatted]["messages"][] = $row;
	}
}
$db = null;

$prefix = '<html lang="ru"><head>
    <title>' . $headTitleName .' - {titleDate}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">

    <style>
        /* General */

@font-face {
    font-family: Whitney;
    src: url(https://cdn.jsdelivr.net/gh/Tyrrrz/DiscordFonts@master/whitney-300.woff);
    font-weight: 300;
}

@font-face {
    font-family: Whitney;
    src: url(https://cdn.jsdelivr.net/gh/Tyrrrz/DiscordFonts@master/whitney-400.woff);
    font-weight: 400;
}

@font-face {
    font-family: Whitney;
    src: url(https://cdn.jsdelivr.net/gh/Tyrrrz/DiscordFonts@master/whitney-500.woff);
    font-weight: 500;
}

@font-face {
    font-family: Whitney;
    src: url(https://cdn.jsdelivr.net/gh/Tyrrrz/DiscordFonts@master/whitney-600.woff);
    font-weight: 600;
}

@font-face {
    font-family: Whitney;
    src: url(https://cdn.jsdelivr.net/gh/Tyrrrz/DiscordFonts@master/whitney-700.woff);
    font-weight: 700;
}

body {
    font-family: "Whitney", "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 17px;
}

a {
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

img {
    object-fit: contain;
}

.markdown {
    max-width: 100%;
    line-height: 1.3;
    overflow-wrap: break-word;
}

.preserve-whitespace {
    white-space: pre-wrap;
}

.spoiler {
    /* width: fit-content; */
    display: inline-block;
    /* This is more consistent across browsers, the old attribute worked well under Chrome but not FireFox. */
}

.spoiler--hidden {
    cursor: pointer;
}

.spoiler-text {
    border-radius: 3px;
}

.spoiler--hidden .spoiler-text {
    color: rgba(0, 0, 0, 0);
}

.spoiler--hidden .spoiler-text::selection {
    color: rgba(0, 0, 0, 0);
}

.spoiler-image {
    position: relative;
    overflow: hidden;
    border-radius: 3px;
}

.spoiler--hidden .spoiler-image {
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.1);
}

.spoiler--hidden .spoiler-image * {
    filter: blur(44px);
}

.spoiler--hidden .spoiler-image:after {
    content: "SPOILER";
    color: #dcddde;
    background-color: rgba(0, 0, 0, 0.6);
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    font-weight: 600;
    padding: 100%;
    border-radius: 20px;
    letter-spacing: 0.05em;
    font-size: 0.9em;
}

.spoiler--hidden:hover .spoiler-image:after {
    color: #fff;
    background-color: rgba(0, 0, 0, 0.9);
}

.quote {
    margin: 0.1em 0;
    padding-left: 0.6em;
    border-left: 4px solid;
    border-radius: 3px;
}

.pre {
    font-family: "Consolas", "Courier New", Courier, monospace;
}

.pre--multiline {
    margin-top: 0.25em;
    padding: 0.5em;
    border: 2px solid;
    border-radius: 5px;
}

.pre--inline {
    padding: 2px;
    border-radius: 3px;
    font-size: 0.85em;
}

.mention {
    border-radius: 3px;
    padding: 0 2px;
    color: #7289da;
    background: rgba(114, 137, 218, .1);
    font-weight: 500;
}

.emoji {
    width: 1.25em;
    height: 1.25em;
    margin: 0 0.06em;
    vertical-align: -0.4em;
}

.emoji--small {
    width: 1em;
    height: 1em;
}

.emoji--large {
    width: 2.8em;
    height: 2.8em;
}

/* Preamble */

.preamble {
    display: grid;
    margin: 0 0.3em 0.6em 0.3em;
    max-width: 100%;
    grid-template-columns: auto 1fr;
}

.preamble__guild-icon-container {
    grid-column: 1;
}

.preamble__guild-icon {
    max-width: 88px;
    max-height: 88px;
}

.preamble__entries-container {
    grid-column: 2;
    margin-left: 0.6em;
}

.preamble__entry {
    font-size: 1.4em;
}

.preamble__entry--small {
    font-size: 1em;
}

/* Chatlog */

.chatlog {
    max-width: 100%;
}

.chatlog__message-group {
    display: grid;
    margin: 0 0.6em;
    padding: 0.9em 0;
    border-top: 1px solid;
    grid-template-columns: auto 1fr;
}

.chatlog__reference-symbol {
    grid-column: 1;
    border-style: solid;
    border-width: 2px 0 0 2px;
    border-radius: 8px 0 0 0;
    margin-left: 16px;
    margin-top: 8px;
}

.chatlog__reference {
    display: flex;
    grid-column: 2;
    margin-left: 1.2em;
    margin-bottom: 0.25em;
    font-size: 0.875em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    align-items: center;
}

.chatlog__reference-avatar {
    border-radius: 50%;
    height: 16px;
    width: 16px;
    margin-right: 0.25em;
}

.chatlog__reference-name {
    margin-right: 0.25em;
    font-weight: 600;
}

.chatlog__reference-link {
    flex-grow: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chatlog__reference-link:hover {
    text-decoration: none;
}

.chatlog__reference-content > * {
    display: inline;
}

.chatlog__reference-edited-timestamp {
    margin-left: 0.25em;
    font-size: 0.8em;
}

.chatlog__author-avatar-container {
    grid-column: 1;
    width: 40px;
    height: 40px;
}

.chatlog__author-avatar {
    border-radius: 50%;
    height: 40px;
    width: 40px;
}

.chatlog__messages {
    grid-column: 2;
    margin-left: 1.2em;
    min-width: 50%;
}

.chatlog__author-name {
    font-weight: 500;
}

.chatlog__timestamp {
    margin-left: 0.3em;
    font-size: 0.75em;
}

.chatlog__message {
    padding: 0.1em 0.3em;
    margin: 0 -0.3em;
    background-color: transparent;
    transition: background-color 1s ease;
}

.chatlog__content {
    font-size: 0.95em;
    word-wrap: break-word;
}

.chatlog__edited-timestamp {
    margin-left: 0.15em;
    font-size: 0.8em;
}

.chatlog__attachment {
    margin-top: 0.3em;
}

.chatlog__attachment-thumbnail {
    vertical-align: top;
    max-width: 45vw;
    max-height: 500px;
    border-radius: 3px;
}

.chatlog__attachment-container {
    height: 40px;
    width: 100%;
    max-width: 520px;
    padding: 10px;
    border: 1px solid;
    border-radius: 3px;
    overflow: hidden;
}

.chatlog__attachment-icon {
    float: left;
    height: 100%;
    margin-right: 10px;
}

.chatlog__attachment-icon > .a {
    fill: #f4f5fb;
    d: path("M50,935a25,25,0,0,1-25-25V50A25,25,0,0,1,50,25H519.6L695,201.32V910a25,25,0,0,1-25,25Z");
}

.chatlog__attachment-icon > .b {
    fill: #7789c4;
    d: path("M509.21,50,670,211.63V910H50V50H509.21M530,0H50A50,50,0,0,0,0,50V910a50,50,0,0,0,50,50H670a50,50,0,0,0,50-50h0V191Z");
}

.chatlog__attachment-icon > .c {
    fill: #f4f5fb;
    d: path("M530,215a25,25,0,0,1-25-25V50a25,25,0,0,1,16.23-23.41L693.41,198.77A25,25,0,0,1,670,215Z");
}

.chatlog__attachment-icon > .d {
    fill: #7789c4;
    d: path("M530,70.71,649.29,190H530V70.71M530,0a50,50,0,0,0-50,50V190a50,50,0,0,0,50,50H670a50,50,0,0,0,50-50Z");
}

.chatlog__attachment-filesize {
    color: #72767d;
    font-size: 12px;
}

.chatlog__attachment-filename {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.chatlog__embed {
    display: flex;
    margin-top: 0.3em;
    max-width: 520px;
}

.chatlog__embed-color-pill {
    flex-shrink: 0;
    width: 0.25em;
    border-top-left-radius: 3px;
    border-bottom-left-radius: 3px;
}

.chatlog__embed-content-container {
    display: flex;
    flex-direction: column;
    padding: 0.5em 0.6em;
    border: 1px solid;
    border-top-right-radius: 3px;
    border-bottom-right-radius: 3px;
}

.chatlog__embed-content {
    display: flex;
    width: 100%;
}

.chatlog__embed-text {
    flex: 1;
}

.chatlog__embed-author {
    display: flex;
    margin-bottom: 0.3em;
    align-items: center;
}

.chatlog__embed-author-icon {
    margin-right: 0.5em;
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.chatlog__embed-author-name {
    font-size: 0.875em;
    font-weight: 600;
}

.chatlog__embed-title {
    margin-bottom: 0.2em;
    font-size: 0.875em;
    font-weight: 600;
}

.chatlog__embed-description {
    font-weight: 500;
    font-size: 0.85em;
}

.chatlog__embed-fields {
    display: flex;
    flex-wrap: wrap;
}

.chatlog__embed-field {
    flex: 0;
    min-width: 100%;
    max-width: 506px;
    padding-top: 0.6em;
    font-size: 0.875em;
}

.chatlog__embed-field--inline {
    flex: 1;
    flex-basis: auto;
    min-width: 150px;
}

.chatlog__embed-field-name {
    margin-bottom: 0.2em;
    font-weight: 600;
}

.chatlog__embed-field-value {
    font-weight: 500;
}

.chatlog__embed-thumbnail {
    flex: 0;
    margin-left: 1.2em;
    max-width: 80px;
    max-height: 80px;
    border-radius: 3px;
}

.chatlog__embed-image-container {
    margin-top: 0.6em;
}

.chatlog__embed-image {
    max-width: 500px;
    max-height: 400px;
    border-radius: 3px;
}

.chatlog__embed-footer {
    margin-top: 0.6em;
}

.chatlog__embed-footer-icon {
    margin-right: 0.2em;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    vertical-align: middle;
}

.chatlog__embed-footer-text {
    font-size: 0.75em;
    font-weight: 500;
}

.chatlog__reactions {
    display: flex;
}

.chatlog__reaction {
    display: flex;
    align-items: center;
    margin: 0.35em 0.1em 0.1em 0.1em;
    padding: 0.2em 0.35em;
    border-radius: 3px;
}

.chatlog__reaction-count {
    min-width: 9px;
    margin-left: 0.35em;
    font-size: 0.875em;
}

.chatlog__bot-tag {
    position: relative;
    top: -.2em;
    margin-left: 0.3em;
    padding: 0.05em 0.3em;
    border-radius: 3px;
    vertical-align: middle;
    line-height: 1.3;
    background: #7289da;
    color: #ffffff;
    font-size: 0.625em;
    font-weight: 500;
}

/* Postamble */

.postamble {
    margin: 1.4em 0.3em 0.6em 0.3em;
    padding: 1em;
    border-top: 1px solid;
}
    </style>
    <style>
        /* General */

body {
    background-color: #36393e;
    color: #dcddde;
}

a {
    color: #0096cf;
}

.spoiler-text {
    background-color: rgba(255, 255, 255, 0.1);
}

.spoiler--hidden .spoiler-text {
    background-color: #202225;
}

.spoiler--hidden:hover .spoiler-text {
    background-color: rgba(32, 34, 37, 0.8);
}

.quote {
    border-color: #4f545c;
}

.pre {
    background-color: #2f3136 !important;
}

.pre--multiline {
    border-color: #282b30 !important;
    color: #b9bbbe !important;
}

/* === Preamble === */

.preamble__entry {
    color: #ffffff;
}

/* Chatlog */

.chatlog__message-group {
    border-color: rgba(255, 255, 255, 0.1);
}

.chatlog__reference-symbol {
    border-color: #4f545c;
}

.chatlog__reference {
    color: #b5b6b8;
}

.chatlog__reference-link {
    color: #b5b6b8;
}

.chatlog__reference-link:hover {
    color: #ffffff;
}

.chatlog__reference-edited-timestamp {
    color: rgba(255, 255, 255, 0.2);
}

.chatlog__author-name {
    color: #ffffff;
}

.chatlog__timestamp {
    color: rgba(255, 255, 255, 0.2);
}

.chatlog__message--highlighted {
    background-color: rgba(114, 137, 218, 0.2) !important;
}

.chatlog__message--pinned {
    background-color: rgba(249, 168, 37, 0.05);
}

.chatlog__attachment-container {
    background-color: #2f3136;
    border-color: #292b2f;
}

.chatlog__edited-timestamp {
    color: rgba(255, 255, 255, 0.2);
}

.chatlog__embed-color-pill--default {
    background-color: rgba(79, 84, 92, 1);
}

.chatlog__embed-content-container {
    background-color: rgba(46, 48, 54, 0.3);
    border-color: rgba(46, 48, 54, 0.6);
}

.chatlog__embed-author-name {
    color: #ffffff;
}

.chatlog__embed-author-name-link {
    color: #ffffff;
}

.chatlog__embed-title {
    color: #ffffff;
}

.chatlog__embed-description {
    color: rgba(255, 255, 255, 0.6);
}

.chatlog__embed-field-name {
    color: #ffffff;
}

.chatlog__embed-field-value {
    color: rgba(255, 255, 255, 0.6);
}

.chatlog__embed-footer {
    color: rgba(255, 255, 255, 0.6);
}

.chatlog__reaction {
    background-color: rgba(255, 255, 255, 0.05);
}

.chatlog__reaction-count {
    color: rgba(255, 255, 255, 0.3);
}

/* Postamble */

.postamble {
    border-color: rgba(255, 255, 255, 0.1);
}

.postamble__entry {
    color: #ffffff;
}
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.6/styles/solarized-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.6/highlight.min.js"></script>
    <script>
        document.addEventListener(\'DOMContentLoaded\', () => {
            document.querySelectorAll(\'.pre--multiline\').forEach(block => hljs.highlightBlock(block));
        });
    </script>

    <script>
        function scrollToMessage(event, id) {
            var element = document.getElementById(\'message-\' + id);

            if (element) {
                event.preventDefault();

                element.classList.add(\'chatlog__message--highlighted\');

                window.scrollTo({
                    top: element.getBoundingClientRect().top - document.body.getBoundingClientRect().top - (window.innerHeight / 2),
                    behavior: \'smooth\'
                });

                window.setTimeout(function() {
                    element.classList.remove(\'chatlog__message--highlighted\');
                }, 2000);
            }
        }

        function showSpoiler(event, element) {
            if (element && element.classList.contains(\'spoiler--hidden\')) {
                event.preventDefault();
                element.classList.remove(\'spoiler--hidden\');
            }
        }
    </script>
</head>
<body><div class="preamble">
    <div class="preamble__guild-icon-container">
        <img class="preamble__guild-icon" src="data/guild.png" alt="Guild icon">
    </div>
    <div class="preamble__entries-container">
        <div class="preamble__entry">Direct Messages</div>
        <div class="preamble__entry">{bodyDate}</div>


    </div>
</div>
<div class="chatlog">';

$suffix = "</div></body></html>";

foreach ($dtoe as $date => $o) {
	$pt = explode("-", $date);
	$hashinfo = intval($pt[0]) * 10000 + intval($pt[1]) * 100 + intval($pt[2]);
	if ($hashinfo < 20170115) {
		continue;
	}
	echo "Writing - " . $date . "\n";
	$fileName = "{$outputFolder}/{$outputFilePrefix}{$date}.html";
	$dateName = $o["date"];
	$document = str_replace(array("{titleDate}", "{bodyDate}"), array($date, $dateName), $prefix);
	$counter = 0;
	foreach ($o["messages"] as $message) {
		echo "Handling " . $message["id"] . "\n";
		$body = $message["body_xml"];
		$matches = array();
		echo "Matching regexes\n";
		preg_match_all("/<a[^>]*href=\\\"([^\"]*)\\\"/", $body, $matches);
		$previews = array();
		if (count($matches[1])) {
			foreach ($matches[1] as $match) {
				try {
					echo "Starting to build preview for " . $match . "\n";
					$previewClient = new Client($match);
					$previewClient->getParser('general')->getReader()->config(['verify' => false]);
					$previewClient->getParser('general')->getReader()->config(['connect_timeout' => 10]);
					$previewClient->getParser('general')->getReader()->config(['timeout' => 10]);
					echo "Getting previews\n";
					$previewsTmp = $previewClient->getPreviews();
					$previewsTmp = array_shift($previewsTmp)->toArray();
					echo "Attempting to build preview " . $match . "\n";
					if (array_key_exists('cover', $previewsTmp) || array_key_exists('embed', $previewsTmp)) {
						$previewsTmp["href"] = $match;
						echo "Found preview!\n";
						$previews[] = $previewsTmp;
					}
				} catch (\Exception $e) {
					echo "Unable to handle preview for $match\n";
				}
			}
		}
		
		
		
		echo "Embedding message in documents\n";
		$document .= "<div class=\"chatlog__message-group\">";
		$avaPath = $defaultAvatar;
		if (array_key_exists($message["from_dispname"], $avatarPathMapping)) {
			$avaPath =  $avatarPathMapping[$message["from_dispname"]];
		}
		$document .= "<div class=\"chatlog__author-avatar-container\">
        <img class=\"chatlog__author-avatar\" src=\"$avaPath\" alt=\"Avatar\"></div>";
		$document .= "<div class=\"chatlog__messages\">";
		$document .= "<span class=\"chatlog__author-name\" >" . $message["from_dispname"]."</span>";
		$dt = date("d.m.Y H:i:s", $message["timestamp"]);
		$document .= "<span class=\"chatlog__timestamp\">$dt</span>";
		$document .= "<div class=\"chatlog__message\">
						<div class=\"chatlog__content\">
							<div class=\"markdown\">
								<span class=\"preserve-whitespace\">". $message["body_xml"] . "</span>
							</div>
						</div>";
		echo "Embedding previews\n";
		foreach ($previews as $preview) {
			$document .= "<div class=\"chatlog__embed\">
                            <div class=\"chatlog__embed-color-pill chatlog__embed-color-pill--default\"></div>
							<div class=\"chatlog__embed-content-container\">";
			$hasTitle = false;
			$href = $preview["href"];
			if (array_key_exists('title', $preview)) {
				if (mb_strlen($preview["title"])) {
					$hasTitle = true;
				}
			}
			$hasDescription = false;
			if (array_key_exists('description', $preview)) {
				if (mb_strlen($preview["description"])) {
					$hasDescription = true;
				}
			}
			if ($hasTitle || $hasDescription) {
				$document .= "<div class=\"chatlog__embed-content\">";
				$document .= "<div class=\"chatlog__embed-text\">";
				if ($hasTitle) {
					$title = $preview["title"];
					$document .= "<div class=\"chatlog__embed-author\">
									<span class=\"chatlog__embed-author-name\">
											<a class=\"chatlog__embed-author-name-link\" href=\"$href\">$title</a>
									</span>
                                  </div>";
				}
				if ($hasDescription) {
					$description = $preview["description"];
					$document .= "<div class=\"chatlog__embed-description\">
                                            <div class=\"markdown preserve-whitespace\">$description</div>
                                  </div>";
				}
				$document .= "</div>";
				$document .= "</div>";
			}
			
			if (array_key_exists('cover', $preview)) {
				$cover = (string)($preview['cover']);
				$localHref = $href;
				$cover2 = try_handle_file($counter, $cover, $date);
				if ($cover != $cover2) {
					$cover = $cover2;
					$localHref = $cover2;
				}
				$document .= "<div class=\"chatlog__embed-image-container\">
                                    <a class=\"chatlog__embed-image-link\" href=\"$localHref\">
                                        <img class=\"chatlog__embed-image\" src=\"$cover\" alt=\"Image\">
                                    </a>
                                </div>";
			}
			if (array_key_exists('embed', $preview)) {
				$cover = $preview['embed'];
				$document .= "<div class=\"chatlog__embed-image-container\">
                                    $cover
                                </div>";
			}
			
			$document .= "<div class=\"chatlog__embed-footer\"><span class=\"chatlog__embed-footer-text\"></span></div>";
			$document .= "</div>";
			$document .= "</div>";
		}
		$document .= "</div>";
		$document .= "</div>";
		$document .= "</div>";
	}
	
	$document .= $suffix;
	file_put_contents($fileName, $document);
}
