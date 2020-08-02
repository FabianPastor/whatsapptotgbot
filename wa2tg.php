#!/usr/bin/env php
<?php
require_once("../classes/bot.class.php");
require_once("../classes/telegram.utils.class.php");
require_once("../classes/ffmpeg.class.php");
use Telegram\Utils as TGUtils;


$config = json_decode(file_get_contents("config.json"));
$bot = new botapi($config->token);
$ffprobe = new ffmpeg(false,"ffprobe");

echo TGUtils\pretty_print_cli(
  $bot->msg($config->root,"Me inicié!\n\n<pre>".TGUtils\pretty_print_html($bot->me)."</pre>","html")
)."\n\n";

$running = true;
while($running){

  $updates = $bot->updates();
  if(empty($updates)) continue;
  if($updates->ok == false){
    echo "Updates Error: ".TGUtils\pretty_print_cli($updates).PHP_EOL;
    continue;
  }
  if(empty($updates->result)) continue;

  //echo "Updates: ".TGUtils\pretty_print_cli($updates).PHP_EOL;
  foreach($updates->result as $update){

    if(isset($update->message)){
      $M = $update->message;

      if($M->chat->type == "private"){
        if( isset($M->text) && substr($M->text, 0, 6) == "/start" ) 
          $bot->msg("{$M->chat->id}", "This bot will recode a Whatsapp Audio file to a Telegram voice message");

        elseif(isset($M->audio))    $fid = &$M->audio->file_id;
        elseif(isset($M->voice))    $fid = &$M->voice->file_id;
        elseif(isset($M->document)) $fid = &$M->document->file_id;

        if(isset($fid)){

          if($filename = $bot->downloadFile($fid)){
            echo "Archivo Descargado".PHP_EOL;
            //echo "Archivo = $filename\n";
            $output = $ffprobe->run("-i $filename");
            if(substr(trim($output), 0, 13) === "Input #0, ogg"){
              $caption = "Audio from {$M->from->first_name}";
              if(isset($M->caption)){
                $caption = $M->caption;
              }
              $bot->uploadVoice($M->chat->id, $filename, $caption);
              echo "Archivo enviado".PHP_EOL;
              unset($caption);
            }
            unlink($filename);
            unset($filename,$output);
          }else{
            $bot->msg("{$M->chat->id}", "Error. Contact the developer.");
            echo "Update: ".TGUtils\pretty_print_cli($update).PHP_EOL;
          }
          unset($fid);
        } // if fid exists
      } //if private
      unset($M);
    } //Isset Message
  } // foreach
} //while
$bot->msg($config->root,"Me Morí!");