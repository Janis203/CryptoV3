<?php
require 'vendor/autoload.php';

use App\Trade;
use App\CoinMarketAPI;

$db = new Sqlite3("storage/database.sqlite");
$db->enableExceptions(true);
$db->exec("CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    symbol TEXT NOT NULL,
    amount REAL NOT NULL,
    price REAL NOT NULL,
    value REAL NOT NULL,
    time TEXT NOT NULL
)");
$db->exec("CREATE TABLE IF NOT EXISTS balance (
    id INTEGER PRIMARY KEY,
    amount REAL NOT NULL
)");
$result = $db->querySingle("SELECT COUNT(*) as count FROM balance");
if ($result == 0) {
    $db->exec("INSERT INTO balance (amount) VALUES(1000.0)");
}
$db->close();

$key = "";
$apiClient = new CoinMarketAPI($key);
$trade = new Trade($apiClient);
while (true) {
    echo "[1] List top crypto currencies\n[2] Search crypto by its ticking symbol
[3] Purchase crypto\n[4] Sell crypto\n[5] Display state of wallet\n[6] Display transaction list\n[Any key] Exit\n";
    $choice = (int)readline("Enter choice ");
    switch ($choice) {
        case 1:
            $trade->list();
            break;
        case 2:
            $symbol = strtoupper(readline("Enter ticking symbol "));
            $trade->search($symbol);
            break;
        case 3:
            $symbol = strtoupper(readline("Enter crypto symbol to purchase "));
            $trade->purchase($symbol);
            break;
        case 4:
            $symbol = strtoupper(readline("Enter crypto symbol to sell "));
            $trade->sell($symbol);
            break;
        case 5:
            $trade->displayWallet();
            break;
        case 6:
            $trade->displayTransactions();
            break;
        default:
            exit("Goodbye\n");
    }
}