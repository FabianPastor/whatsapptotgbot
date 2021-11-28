<?php 
require_once("../classes/bot.class.php");
require_once("../classes/ffprobe.class.php");

use SFPL\Telegram\BotAPI;
use SFPL\FFMPEG\ffprobe;

class Config
{
  public int $root;
  public string $token;
  public string|null $ffmpeg_path;
}
/** @var Config $config */
$config = json_decode(file_get_contents("config.json"));

$bot = new BotAPI($config->token);

$ffprobe = new ffprobe( $config->ffmpeg_path ?? null );