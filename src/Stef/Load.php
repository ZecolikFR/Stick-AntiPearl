<?php

namespace Stef;

use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Load extends PluginBase implements Listener
{
    private array $playerCooldowns = [];
    private array $entityCooldowns = [];
    private string $itemName;
    private int $cooldownTime;
    private int $entityCooldownTime;

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();
        $config = $this->getConfig();
        
        $this->itemName = StringToItemParser::getInstance()->parse($config->getNested("Item.name"))->getName();
        $this->cooldownTime = $config->get("cooldown");
        $this->entityCooldownTime = $config->get("time");

        $this->getLogger()->info("By stefaneh.");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        $entity = $event->getEntity();

        if (!$damager instanceof Player || !$entity instanceof Player) {
            return;
        }

        $damagerName = $damager->getName();
        $entityName = $entity->getName();
        $itemInHand = $damager->getInventory()->getItemInHand()->getName();

        if ($itemInHand !== $this->itemName) {
            return;
        }

        
        if ($this->isInCooldown($damagerName, $this->playerCooldowns, $this->cooldownTime)) {
            $this->sendCooldownMessage($damager, $this->cooldownTime, $this->playerCooldowns[$damagerName], "msg-cooldown-user");
            $event->cancel();
            return;
        }

        $this->playerCooldowns[$damagerName] = time();

        
        if ($this->isInCooldown($entityName, $this->entityCooldowns, $this->entityCooldownTime)) {
            $damager->sendMessage(str_replace('{player}', $entityName, $this->getConfig()->get("msg-already")));
        } else {
            $this->entityCooldowns[$entityName] = time();
        }
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $itemUsed = $event->getItem()->getName();
        $config = $this->getConfig();
        $enderPearlName = StringToItemParser::getInstance()->parse(ItemTypeNames::ENDER_PEARL)->getName();

        if ($itemUsed !== $enderPearlName) {
            return;
        }

        
        if ($this->isInCooldown($player->getName(), $this->entityCooldowns, $this->entityCooldownTime)) {
            $this->sendCooldownMessage($player, $this->entityCooldownTime, $this->entityCooldowns[$player->getName()], "msg-cooldown");
            $event->cancel();
        }
    }

    private function isInCooldown(string $name, array $cooldownArray, int $cooldownDuration): bool
    {
        return isset($cooldownArray[$name]) && (time() - $cooldownArray[$name]) < $cooldownDuration;
    }

    private function sendCooldownMessage(Player $player, int $cooldownDuration, int $lastUsed, string $messageKey): void
    {
        $remainingTime = $cooldownDuration - (time() - $lastUsed);
        $player->sendMessage(str_replace('{time}', $remainingTime, $this->getConfig()->get($messageKey)));
    }
}
