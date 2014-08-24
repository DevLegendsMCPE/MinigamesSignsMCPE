<?php

namespace MiniGSigns;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;
use pocketmine\tile\Tile;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\level\Level;

Class main extends PluginBase implements Listener{
    public $temp, $teleport;
    
    public function onLoad(){
    }
    
    public function onEnable(){
        $this->timer = new Timer($this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->timer, 1 * 15);
        $this->getLogger()->info(TextFormat::DARK_PURPLE ."MiniGSigns Enabled!");
        $this->config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function PlayerBlockPlace(BlockPlaceEvent $event){
        $block = $event->getBlock()->getID();
        $player = $event->getPlayer();
        if($block == 323 || $block == 63 || $block == 68){
                $x=(Int) $event->getBlock()->getX();
                $y=(Int) $event->getBlock()->getY();
                $z=(Int) $event->getBlock()->getZ();            
                $world = $event->getBlock()->getLevel();
                $var = $x.":".$y.":".$z.":".$world->getName();
                if($player->isOp()==true){
                    if(isset($this->temp)) {
                    if(array_key_exists($player->getName(), $this->temp)){
                    $tx = $this->temp[$player->getName()]["tx"];
                    $ty = $this->temp[$player->getName()]["ty"];
                    $tz = $this->temp[$player->getName()]["tz"];
                    $tlevel = $this->temp[$player->getName()]["level"];
                    $name = strtoupper($this->temp[$player->getName()]["name"]);
                    $max = $this->temp[$player->getName()]["max"];
                    $pos= new Vector3($x, $y, $z);
                    $current = 0;
                    $this->MakeSign($pos, $world, $name, $max, $current);
                    $this->config->set("x:" . $x . "y:" . $y . "z:" . $z . "world:" . $world->getName(), array("name" => $name, "max" => $max, "tx" => $tx, "ty" => $ty, "tz" => $tz, "current" => 0));
                    $this->config->set($tlevel, array("x" => $x, "y" => $y, "z" => $z, "signlevel" => $world->getID(), "name" => $name, "max" => $max));
                    unset($this->temp[$player->getName()]);
                    $player->sendMessage("MiniGame Sign Created!");
                 }
            }
          }
        }
    }
    
    public function PlayerBlockTap(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock()->getID();
        $bpos = $event->getBlock();
        $x = (Int) $bpos->getX();
        $y = (Int) $bpos->getY();
        $z = (Int) $bpos->getZ();
        $world = $bpos->getLevel()->getName();
        if(array_key_exists("x:" . $x . "y:" . $y . "z:" . $z . "world:" . $world, $this->config->getAll())) {
            $signdata = $this->config->get("x:" . $x . "y:" . $y . "z:" . $z . "world:" . $world);
            if($signdata["current"] > $signdata["max"]){
                if($player->isOp()==false) {
                $player->sendMessage("This game is full, try again later!");
                }
                else {
                    $pos = new Vector3($signdata["tx"], $signdata["ty"], $signdata["tz"]);
                    $player->teleport($pos);
                    $player->sendMessage("Teleporting to " . $signdata["name"]);
                }
            }
            else {
                $pos = new Vector3($signdata["tx"], $signdata["ty"], $signdata["tz"]);
                $player->teleport($pos);
                $player->sendMessage("Teleporting to " . $signdata["name"]);
            }
        }
        
    }
    
    public function PlayerBlockBreak(BlockBreakEvent $event){
        
    }
    
    public function MakeSign(Vector3 $pos, $world, $name, $max, $current){
        $sign = new Sign($world->getChunkAt($pos->x >> 4, $pos->z >> 4), new Compound("", array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", $name),
            new String("Text2", "Players: ".$current."/".$max),
            new String("Text3", "Tap sign to go"),
            new String("Text4", "to minigame!")
            )));
        $sign->saveNBT();
        $sign->spawntoAll();
        
    }
    public function onCommand(\pocketmine\command\CommandSender $sender, Command $command, $label, array $args){
        if($command->getName()== "mgsign"){ 
             if($sender->isOp()==true){
                    if($args[0] == false){
                        $sender->sendMessage("Usage: /mgsign create <GameName> <MaxPlayers>");
                        $sender->sendMessage("Usage: /mgsign setwarp");
                    }
                    
                    switch ($args[0]){
                        case "create":
                    if($args[1]==false || $args[2]==false) {
                        $sender->sendMessage("[MGSign] Invalid Arguments");
                    }
                    else {

                    $name = $args[1];
                    $max = $args[2];
                    if(!is_numeric($max)){
                        $sender->sendMessage("MaxPlayers must be a number");
                        break;
                    }
                    if(is_numeric($name)){
                        $sender->sendMessage("GameName must be text");
                        break;
                    }
                    if($max <= 0){
                        $sender->sendMessage("Maximum players must be greater than 0");
                        break;
                    }
                    $this->temp[$sender->getName()] = array("name" => $name, "max" => $max);
                    $sender->sendMessage("Name and Max saved!");
                    $sender->sendMessage("Please Choose a Minigame Spawn");
                    }
             
             break;
                        case "setwarp":
                $x = $sender->getX();
                $y = $sender->getY();
                $z = $sender->getZ();
                $world = $sender->getLevel()->getName();
                if(isset($this->temp[$sender->getName()])) {
                $this->temp[$sender->getName()]["tx"] = $x;
                $this->temp[$sender->getName()]["ty"] = $y;
                $this->temp[$sender->getName()]["tz"] = $z;
                $this->temp[$sender->getName()]["level"] = $world;
                $sender->sendMessage("MiniGame Spawn Set!");
                }
                else { 
                    $sender->sendMessage("Do /mgsign create <GameName> <MaxPlayers> First!");
                }
                }
            }
            else{
                $sender->sendMessage("You need to be admin/op to run this command.");
            }
         }
    }
    public function updateSigns(){
       foreach($this->getServer()->getLevels() as $level) {
          $players = $level->getPlayers();
          $lname = $level->getName();
          if(array_key_exists($lname, $this->config->getAll())) {
              $current = count($players);
              $world = $this->getServer()->getLevel($this->config->get($lname)["signlevel"]);
              $config = $this->getConfig()->get($lname);
          $pos = new Vector3($config["x"], $config["y"], $config["z"]);
          $this->MakeSign($pos, $world, $config["name"], $config["max"], $current);
          }
       }
    }
}

