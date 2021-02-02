# discord-skype-history2html
A set of scripts to convert discord and skype history to html

This repo contains simple PHP scripts to contain Discord ans Skype history into html, splitted by days. 
To do this, you should follow the processes.

The scripts provided as-is and should be edited to make stuff work.

## Before running scripts.
You should run "composer install" in "skype" and "discord" folder to install required dependencies 

## for Discord
1. Use https://github.com/Tyrrrz/DiscordChatExporter to export history as HTML
2. Create folder structure, where output should be stored (you should make something like folder "outdiscord" and "data" folder in it). Download guild icon and avatars to place them into "data" folder.
3. Edit and run discord/split.php to split history by days
4. Edit and run discord/downloadpreviews.php to download and store previews locally.
5. Use template/stringstomenu.php and HTML files in template folder to form index file and menu

## for Skype
1. Find and locate main.db (https://www.laptopmag.com/how-to/back-up-skype-chat-history-windows-10#:~:text=The%20database%20file%20is%20updated,db.)
2. Create folder structure, where output should be stored (you should make something like folder "outskype" and "data" folder in it). Download guild icon and avatars to place them into "data" folder.
3. Edit and run skype/parser.php to create html files of history.
4. Use template/stringstomenu.php and HTML files in template folder to form index file and menu.

## Known issues:
Some previews for Youtube are not built, this seems to be an issue, that needs further investigation
