#!/usr/bin/env php
<?php
require_once("bootstrap.php");

echo var_export($bot->me,true).PHP_EOL;
$bot->msg($config->root,"I am alive!");

$running = true;
while($running){
  
  $updates = $bot->updates();
  if(empty($updates)){
    sleep(1);
    continue;
  }
  
  if($updates->ok == false){
    echo "Updates Error: ".var_export($updates, true).PHP_EOL;
    sleep(1);
    continue;
  }
  if(empty($updates->result)) continue;
  
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
            echo "File Downloaded $filename".PHP_EOL;
            if( $output = $ffprobe->run("-print_format json -i $filename") ){
              if( $obj = json_decode($output->stdout) ){
                if(
                  isset($obj->format) && (
                    $obj->format->format_name == "ogg" && 
                    $obj->streams[0]->codec_name == "opus"
                  )
                ){
                  $caption = "Audio from {$M->from->first_name}";
                  if(isset($M->caption)){
                    $caption = $M->caption;
                  }
                  $bot->uploadVoice($M->chat->id, $filename, $caption);
                  echo "  File sent".PHP_EOL;
                  unset($caption);
                }else{
                  $bot->msg($M->chat->id, "Error: File is not an ogg and opus encoded. (not whatsapp voice)");
                }
                unset($obj);
              }else{
                echo "Json couldn't get parsed: ".PHP_EOL."JSON: {$output}".PHP_EOL;
                $error = true;
              }
              unset($output);
            }else{
              echo "ffprobe run error.".PHP_EOL; //TODO: add more info to the report..
              $error = true;
            }
            unlink($filename); //Delete the downloaded file
          }else{
            echo "File Coldn't be downloaded.".PHP_EOL;
            $error = true;
          }
          unset($fid);
          
          if(isset($error)){
            $randcheck = str_pad(dechex(random_int(PHP_INT_MIN, PHP_INT_MAX)),16,"0",0);
            $bot->msg($M->chat->id, "Error: Please contact the developer and give him this code: #{$randcheck}");
            echo "Update #{$randcheck}: ".var_export($update, true).PHP_EOL;
            unset($error);
          }
        } // if fid exists
      } //if private
      unset($M);
    } //Isset Message
  } // foreach
} //while
$bot->msg($config->root, "I am ded!"); //this should never happen