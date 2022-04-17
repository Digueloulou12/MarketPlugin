<?php

namespace Digueloulou12\Command;

use pocketmine\command\CommandSender;
use Digueloulou12\API\MarketAPI;
use pocketmine\item\ItemFactory;
use pocketmine\command\Command;
use Digueloulou12\MarketMain;
use pocketmine\player\Player;

class MarketCommand extends Command
{
    public function __construct(string $name, string $description = "", array $aliases = [])
    {
        parent::__construct($name, $description, null, $aliases);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                if ($args[0] === "sell") {
                    $config = MarketMain::getInstance()->getConfig();
                    $id = $sender->getInventory()->getItemInHand()->getId() . ":" . $sender->getInventory()->getItemInHand()->getMeta();
                    if (!in_array($id, $config->get("blacklist-items"))) {
                        if (explode(":", $id)[0] != 0) {
                            if (isset($args[1]) and is_numeric($args[1])) {
                                if (($args[1] >= $config->get("price_min")) and ($args[1] <= $config->get("price_max"))) {
                                    MarketMain::getMarketInventory()->data->set($sender->getName() . "-" . mt_rand(0, 999999999), ["date" => date("F j, Y, g:i a"), "price" => $args[1], "item" => $sender->getInventory()->getItemInHand()->jsonSerialize()]);
                                    $sender->getInventory()->setItemInHand(ItemFactory::air());
                                    $sender->sendMessage(MarketAPI::getConfigMessage("sell_item"));
                                } else $sender->sendMessage(MarketAPI::getConfigMessage("no_valid_price"));
                            } else $sender->sendMessage(MarketAPI::getConfigMessage("price_is_numeric"));
                        } else $sender->sendMessage(MarketAPI::getConfigMessage("no_sell_item_air"));
                    } else $sender->sendMessage(MarketAPI::getConfigMessage("item_blacklisted"));
                } elseif ($args[0] === "about") {
                    $sender->sendMessage("------ MarketPlugin ------");
                    $sender->sendMessage("- PluginShop: https://shoptly.com/digueloulou12");
                    $sender->sendMessage("- Author : Digueloulou12");
                    $sender->sendMessage("- Version: 1.0");
                    $sender->sendMessage("--------------------------");
                } else $sender->sendMessage(MarketAPI::getConfigMessage("command_usage"));
            } else MarketMain::getMarketInventory()->sendMarketInventory($sender);
        }
    }
}