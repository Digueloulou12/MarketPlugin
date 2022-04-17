<?php

namespace Digueloulou12\MarketInventory;

use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\Inventory;
use Digueloulou12\API\MarketAPI;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use Digueloulou12\MarketMain;
use pocketmine\item\ItemIds;
use pocketmine\utils\Config;
use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\world\Explosion;

class MarketInventory
{
    public Config $data;

    public function __construct()
    {
        $this->data = new Config(MarketMain::getInstance()->getDataFolder() . "Market.json", Config::JSON);
    }

    public function sendMarketInventory(Player $player): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName(MarketAPI::getConfigMessage("title"));
        $this->setBaseInventory($menu->getInventory());
        $menu->setListener(function (InvMenuTransaction $transaction) use ($player): InvMenuTransactionResult {
            $inventory = $transaction->getAction()->getInventory();

            if ($transaction->getItemClicked()->getNamedTag()->getTag("sell") !== null) {
                $inventory->clearAll();
                $back_item = MarketAPI::setItemMarket(MarketAPI::getItemByPath("back"));
                $back_item->getNamedTag()->setString("back", "back");
                $inventory->setItem(45, $back_item);
                foreach ($this->data->getAll() as $seller => $key) {
                    if (explode("-", $seller)[0] === $player->getName()) {
                        $item = Item::jsonDeserialize($key["item"]);
                        $item->getNamedTag()->setString("my", "my_sell");
                        $item->getNamedTag()->setString("path", $seller);
                        $inventory->addItem($item);
                    }
                }
                return $transaction->discard();
            }

            if ($transaction->getItemClicked()->getNamedTag()->getTag("back_page") !== null) {
                if ($transaction->getItemClicked()->getNamedTag()->getInt("page") > 1) {
                    $this->setBaseInventory($inventory, $transaction->getItemClicked()->getNamedTag()->getInt("page") - 1);
                }
                return $transaction->discard();
            }

            if ($transaction->getItemClicked()->getNamedTag()->getTag("next") !== null) {
                $max_page = ceil(count($this->data->getAll()) / 46);
                if ($transaction->getItemClicked()->getNamedTag()->getInt("page") < $max_page) {
                    $this->setBaseInventory($inventory, $transaction->getItemClicked()->getNamedTag()->getInt("page") + 1);
                }
                return $transaction->discard();
            }

            if ($transaction->getItemClicked()->getNamedTag()->getTag("back") !== null) {
                $this->setBaseInventory($inventory);
                return $transaction->discard();
            }

            if ($transaction->getItemClicked()->getNamedTag()->getTag("buy") !== null) {
                if (!$this->data->exists($inventory->getItem(13)->getNamedTag()->getString("path"))) {
                    $player->sendMessage(MarketAPI::getConfigMessage("item_already_sell"));
                    $this->setBaseInventory($inventory);
                    return $transaction->discard();
                }

                $money = $this->data->get($inventory->getItem(13)->getNamedTag()->getString("path"))["price"];
                if (MarketAPI::getMoney($player->getName()) < $money) {
                    $player->sendMessage(MarketAPI::getConfigMessage("no_money"));
                    return $transaction->discard();
                }

                MarketAPI::removeMoney($player->getName(), $money);
                MarketAPI::addMoney(explode("-", $inventory->getItem(13)->getNamedTag()->getString("path"))[0], $money);

                $item = Item::jsonDeserialize($this->data->get($inventory->getItem(13)->getNamedTag()->getString("path"))["item"]);
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else $player->getWorld()->dropItem($player->getPosition(), $item);

                $this->data->remove($inventory->getItem(13)->getNamedTag()->getString("path"));

                $this->setBaseInventory($inventory);
                return $transaction->discard();
            }

            if ($transaction->getItemClicked()->getNamedTag()->getTag("market") !== null) return $transaction->discard();

            if ($transaction->getItemClicked()->getNamedTag()->getTag("path") === null) return $transaction->discard();

            if (!$this->data->exists($transaction->getItemClicked()->getNamedTag()->getString("path"))) {
                $player->sendMessage(MarketAPI::getConfigMessage("item_already_sell"));
                $this->setBaseInventory($inventory);
                return $transaction->discard();
            }

            if ($transaction->getItemClicked()->getNamedTag()->getTag("my") !== null) {
                if (!$this->data->exists($transaction->getItemClicked()->getNamedTag()->getString("path"))) {
                    $player->sendMessage(MarketAPI::getConfigMessage("item_already_sell"));
                    return $transaction->discard();
                }

                $item = Item::jsonDeserialize($this->data->get($transaction->getItemClicked()->getNamedTag()->getString("path"))["item"]);
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else $player->getWorld()->dropItem($player->getPosition(), $item);

                $this->data->remove($transaction->getItemClicked()->getNamedTag()->getString("path"));
                $player->sendMessage(MarketAPI::getConfigMessage("retrieve_item"));
                $this->setBaseInventory($inventory);
                return $transaction->discard();
            }

            $item = Item::jsonDeserialize($this->data->get($transaction->getItemClicked()->getNamedTag()->getString("path"))["item"]);
            $item->getNamedTag()->setString("path", $transaction->getItemClicked()->getNamedTag()->getString("path"));
            $this->setBuyInventory($inventory, $item);

            return $transaction->discard();
        });
        $menu->send($player);
    }

    private function setBaseInventory(Inventory $inventory, int $page = 1): void
    {
        $inventory->clearAll();
        $back = MarketAPI::setItemMarket(MarketAPI::getItemByPath("back"));
        $back->getNamedTag()->setString("back_page", "back_page");
        $back->getNamedTag()->setInt("page", $page);
        $inventory->setItem(48, $back);
        $inventory->setItem(49, MarketAPI::setItemMarket(MarketAPI::getItemByPath("page", "{page}", $page)));
        $next = MarketAPI::setItemMarket(MarketAPI::getItemByPath("next"));
        $next->getNamedTag()->setString("next", "next");
        $next->getNamedTag()->setInt("page", $page);
        $inventory->setItem(50, $next);
        $sell_item = MarketAPI::getItemByPath("my_sales");
        $sell_item->getNamedTag()->setString("sell", "sell");
        $inventory->setItem(53, MarketAPI::setItemMarket($sell_item));

        $items = count($this->data->getAll());
        if ($items < 1) $items = 1;
        $max_page = ceil($items / 46);

        if ($page > $max_page) {
            $this->setBaseInventory($inventory);
            return;
        }

        $min_item = 46 * ($page - 1);

        $slot = 0;
        $int_item = 1;
        foreach ($this->data->getAll() as $seller => $key) {
            if ($int_item >= $min_item) {
                if ($slot > 44) break;
                $item = Item::jsonDeserialize($key["item"]);
                $item->getNamedTag()->setString("path", $seller);
                $inventory->setItem($slot, $item);
                $slot++;
            }
            $int_item++;
        }
    }

    private function setBuyInventory(Inventory $inventory, Item $item): void
    {
        $outline = [0, 1, 2, 3, 4, 5, 6, 7, 8, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 9, 18, 27, 36, 17, 26, 35];
        foreach ($outline as $slot) {
            $inventory->setItem($slot, MarketAPI::setItemMarket(MarketAPI::getItemByPath("outline")));
        }

        $buy = [14, 15, 16, 23, 24, 25, 32, 33, 34, 41, 42, 43];
        $buy_item = MarketAPI::getItemByPath("confirm_buy");
        $buy_item->getNamedTag()->setString("buy", "buy");
        foreach ($buy as $slot) {
            $inventory->setItem($slot, $buy_item);
        }

        $back = [10, 11, 12, 19, 20, 21, 28, 29, 30, 37, 38, 39];
        $back_item = MarketAPI::getItemByPath("back_buy");
        $back_item->getNamedTag()->setString("back", "back");
        foreach ($back as $slot) {
            $inventory->setItem($slot, $back_item);
        }

        $price = $this->data->get($item->getNamedTag()->getString("path"))["price"];
        $seller = explode("-", $item->getNamedTag()->getString("path"))[0];
        $date = $this->data->get($item->getNamedTag()->getString("path"))["date"];

        $inventory->setItem(22, MarketAPI::setItemMarket(MarketAPI::getItemByPath("info_price", "{price}", $price)));
        $inventory->setItem(31, MarketAPI::setItemMarket(MarketAPI::getItemByPath("info_seller", "{seller}", $seller)));
        $inventory->setItem(40, MarketAPI::setItemMarket(MarketAPI::getItemByPath("info_date", "{date}", $date)));

        $inventory->setItem(13, $item);
    }
}