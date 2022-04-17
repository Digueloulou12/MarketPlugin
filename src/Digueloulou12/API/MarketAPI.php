<?php

namespace Digueloulou12\API;

use pocketmine\item\ItemFactory;
use Digueloulou12\MarketMain;
use pocketmine\item\Item;

class MarketAPI
{
    public static function getConfigMessage(string $path, array|string $replace = [], array|string $replacer = []): string
    {
        $msg = str_replace("{prefix}", MarketMain::getInstance()->getConfig()->get("prefix"), MarketMain::getInstance()->getConfig()->get($path));
        return str_replace($replace, $replacer, $msg);
    }

    public static function getItemByPath(string $path, array|string $replace = [], array|string $replacer = []): Item
    {
        $i = MarketMain::getInstance()->getConfig()->get($path);
        return ItemFactory::getInstance()->get($i[0] ?? 0, $i[1] ?? 0, 1)->setCustomName(str_replace($replace, $replacer, $i[2] ?? ""));
    }

    public static function countPlayerSellItem(string $name): int
    {
        $count = 0;
        foreach (MarketMain::getMarketInventory()->data->getAll() as $player => $key) {
            if ($name === explode("-", $player)[0]) $count++;
        }
        return $count;
    }

    public static function addMoney(string $name, int $amount): void
    {
        if (MarketMain::getEconomyPlugin()->getName() === "EconomyAPI") {
            MarketMain::getEconomyPlugin()->addMoney($name, $amount);
        }
    }

    public static function getMoney(string $name): ?int
    {
        if (MarketMain::getEconomyPlugin()->getName() === "EconomyAPI") {
            return MarketMain::getEconomyPlugin()->myMoney($name);
        }
        return null;
    }

    public static function removeMoney(string $name, int $amount): void
    {
        if (MarketMain::getEconomyPlugin()->getName() === "EconomyAPI") {
            MarketMain::getEconomyPlugin()->reduceMoney($name, $amount);
        }
    }

    public static function setItemMarket(Item $item): Item
    {
        $item->getNamedTag()->setString("market", "market");
        return $item;
    }
}