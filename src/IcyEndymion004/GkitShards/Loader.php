<?php

namespace IcyEndymion004\GkitShards;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\nbt\tag\ByteTag;

use IcyEndymion004\GkitShards\libs\muqsit\invmenu\inventories\BaseFakeInventory;
use IcyEndymion004\GkitShards\libs\muqsit\invmenu\inventories\DoubleChestInventory;
use IcyEndymion004\GkitShards\libs\muqsit\invmenu\InvMenuHandler;
use IcyEndymion004\GkitShards\libs\muqsit\invmenu\InvMenu;
use Stringable;

class Loader extends PluginBase implements Listener {

    /**
     * @var Config
     */
    private $shardData;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->saveDefaultConfig();
        $this->shardData = new Config($this->getDataFolder() . "sharddata.yml", Config::YAML);  
                @mkdir($this->getDataFolder());
        $this->saveResource("config.yml");
        if (!$this->getConfig()->exists("config-version")) {
			      $this->getLogger()->notice("§eYour configuration file is from another version. Updating the Config...");
			      $this->getLogger()->notice("§eThe old configuration file can be found at config_old.yml");
			      rename($this->getDataFolder()."config.yml", $this->getDataFolder()."config_old.yml");
			      $this->saveResource("config.yml");
			      return;
		    }
		    if (version_compare("0.0.7", $this->getConfig()->get("config-version"))) {
            $this->getLogger()->notice("§eYour configuration file is from another version. Updating the Config...");
			      $this->getLogger()->notice("§eThe old configuration file can be found at config_old.yml");
			      rename($this->getDataFolder()."config.yml", $this->getDataFolder()."config_old.yml");
			      $this->saveResource("config.yml");
			      return;
        }
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
    }

    public function onInteract(PlayerInteractEvent $event): void{
        $NoRoomMessage = $this->getConfig()->get("NoRoomMessage");
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($item->getNamedTag()->hasTag("invdata")){
            $val = $item->getNamedTag()->getTag("invdata")->getValue();
            $contents = $this->getInvContents($val);
            if($player->getInventory()->firstEmpty() === -1) {
                $player->sendMessage($NoRoomMessage);
                return;
            }
            foreach($contents as $content){
                $player->getInventory()->addItem($content);
            }
            self::pop($player);
        }
    }

    public static function pop(Player $player): void{
        $index = $player->getInventory()->getHeldItemIndex();
        $item = $player->getInventory()->getItemInHand();
        $player->getInventory()->setItem($index, $item->setCount($item->getCount() - 0.5));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {  

        $types = $this->getConfig()->get("type-shards");
        if(!isset($types[$args[0]])){ 
            
            $invaildshard = str_replace("{shard}", $args[0], $this->getConfig()->get("InvaidShard"));
            $invaildshard = str_replace("{player}", $sender->getName(), $invaildshard);
                $sender->sendMessage($invaildshard);
                return false;
        }
        $shardname = $args[0]; //The Thing You Entered for the shard is not a vaild shard

        $NoShardexists = str_replace("{shard}", $shardname, $this->getConfig()->get("NoShardexistsmsg"));
        $NoShardexists = str_replace("{player}", $sender->getName(), $NoShardexists);
        $SetInvasShard = str_replace("{shard}", $shardname, $this->getConfig()->get("SetInvasShardmsg"));
        $SetInvasShard = str_replace("{player}", $sender->getName(), $SetInvasShard);
        $GivenShard = str_replace("{shard}", $shardname, $this->getConfig()->get("GivenShardMsg"));
        $GivenShard = str_replace("{player}", $sender->getName(), $GivenShard);
        
        if($command->getName() === "setshardinv"){
            if(!$sender instanceof Player) return false;
            if(!isset($types[$args[0]])){
                $sender->sendMessage($NoShardexists);
                return false;
            }
            $sender->sendMessage($SetInvasShard);
            $this->setContentsToFile($args[0], $sender->getInventory()->getContents());
        }
        if($command->getName() === "giveshard"){
            if(!$sender instanceof Player) return false;
            if(!isset($types[$args[0]])){ 
                $sender->sendMessage($NoShardexists);
                return false;
            }
            $ShardItemId = $this->getConfig()->get("shardID");
            $ShardItemMeta = $this->getConfig()->get("shardMeta");
            $item = ItemFactory::get($ShardItemId, $ShardItemMeta, 1);
            $item->setCustomName($this->getConfig()->get("type-shards")[$args[0]]["item-name"]);
            $item->setLore($this->getConfig()->get("type-shards")[$args[0]]["item-lore"]);
            $item->setNamedTagEntry(new StringTag("invdata", $args[0]));
            $item->setNamedTagEntry(new ListTag("ench", []));
            $sender->getInventory()->addItem($item);
            $sender->sendMessage($GivenShard);
        }
        return true;
    }

    public function getShardData(): Config{
        return $this->shardData;
    }

    public function setContentsToFile(string $shard, array $contents): void{
        foreach($contents as $key => $value){
            /** @var Item $value */
            $contents[$key] = $value->jsonSerialize();
        }
        $this->getShardData()->set($shard, $contents);
        $this->getShardData()->save();
    }

    /**
     * Set contents
     * @param string $shard
     * @return array
     */
    public function getInvContents(string $shard): array{
        $data = $this->getShardData()->get($shard);
        foreach($data as $key => $value){
            $data[$key] = Item::jsonDeserialize($value);
        }
        return $data;
}
}
