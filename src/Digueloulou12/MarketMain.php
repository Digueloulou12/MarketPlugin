<?php

namespace Digueloulou12;

use Digueloulou12\MarketInventory\MarketInventory;
use pocketmine\utils\AssumptionFailedError;
use Digueloulou12\Command\MarketCommand;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;

class MarketMain extends PluginBase
{
    private static MarketInventory $marketInventory;
    private static MarketMain $main;
    private static Plugin $economy;

    public function onEnable(): void
    {
        self::$main = $this;
        $this->saveDefaultConfig();
        self::$marketInventory = new MarketInventory();

        if ($this->getConfig()->get("version") !== 1.0) {
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
            $this->saveDefaultConfig();
            $this->getLogger()->info("The config.yml file was no longer up to date, it was renamed to config_old.yml and a new updated config.yml file was created.");
        }

        $economy = $this->getConfig()->get("economy") ?? "EconomyAPI";
        if ($this->getServer()->getPluginManager()->getPlugin($economy) === null) {
            $this->getLogger()->alert("THE MARKETPLUGIN PLUGIN HAS BEEN DEACTIVATED BECAUSE IT HAS NOT FOUND THE PLUGIN $economy !");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        self::$economy = $this->getServer()->getPluginManager()->getPlugin($economy);

        if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);

        $command = $this->getConfig()->get("command");
        $this->getServer()->getCommandMap()->register("MarketPlugin", new MarketCommand($command[0] ?? "market", $command[1] ?? "", $this->getConfig()->get("command_aliases") ?? []));
    }

    public static function getMarketInventory(): MarketInventory
    {
        return self::$marketInventory;
    }

    public static function getEconomyPlugin(): Plugin
    {
        return self::$economy;
    }

    public function onDisable(): void
    {
        try {
            self::getMarketInventory()->data->save();
        } catch (\JsonException $e) {
            throw new AssumptionFailedError("Error while saving the market");
        }
    }

    public static function getInstance(): self
    {
        return self::$main;
    }
}