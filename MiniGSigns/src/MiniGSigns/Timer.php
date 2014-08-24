<?php

namespace MiniGSigns;

use pocketmine\scheduler\PluginTask;

class Timer extends PluginTask{
    
    public function onRun($currentTick){
        $this->getOwner()->updateSigns();  
    }
}

