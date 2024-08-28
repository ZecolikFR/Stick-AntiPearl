<?php

namespace Stef;


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Load extends PluginBase implements Listener
{
    private array $c = [];
    private array $cd = [];


protected function onEnable(): void
{
    $this->saveDefaultConfig();
    $this->getLogger()->info(" By stefaneh.");
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
}

public function Activate(EntityDamageByEntityEvent $e){
        $cfg = $this->getConfig();
    $damager = $e->getDamager();
    $p = $e->getEntity();
    if($p instanceof Player && $damager instanceof Player){
        $psd = $p->getName();
        $ps = $damager->getName();
        $id = $damager->getInventory()->getItemInHand()->getName();
       if($id ===  LegacyStringToItemParser::getInstance()->parse($cfg->getNested("Item.name"))->getName()){
           if(isset($this->c[$ps]) && time() - $this->c[$ps] < $cfg->get("cooldown")){
               $e->cancel();
               $restant = $cfg->get("cooldown") - (time() - $this->c[$ps]);
               $damager->sendMessage(str_replace('{time}',$restant,$cfg->get("msg-cooldown-user")));
           }else{
               $this->c[$ps] = time();
               if(isset($this->cd[$psd]) && time() - $this->cd[$psd] < $cfg->get("time")){
                   $damager->sendMessage(str_replace('{player}',$p->getName(),$cfg->get("msg-already")));
               }else{
                   $this->cd[$psd] = time();
               }
           }

       }
    }
}

public function UsePearl(PlayerItemUseEvent $e){
        $cfg  = $this->getConfig();
        $p = $e->getPlayer();
        $psd = $p->getName();
        $id = $e->getItem()->getName();
        if($id === LegacyStringToItemParser::getInstance()->parse(VanillaItems::ENDER_PEARL()->getName())->getName()){
            if(isset($this->cd[$psd]) && time() - $this->cd[$psd] < $cfg->get("time")){
                $e->cancel();
                $restant = $cfg->get("time") - (time() - $this->cd[$psd]);
                $p->sendMessage(str_replace('{time}',$restant,$cfg->get("msg-cooldown")));
            }
        }
}

}