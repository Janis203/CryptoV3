<?php

namespace App;

use Exception;
use SQLite3;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class Trade
{
    private ApiClient $apiClient;
    private SQLite3 $db;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->db = new SQLite3(__DIR__ . '/../storage/database.sqlite');
    }

    public function list(): void
    {
        try {
            $data = $this->apiClient->getList(1, 10, 'USD');
            if (isset($data["data"])) {
                $output = new ConsoleOutput();
                $table = new Table($output);
                $table->setHeaders(["Rank", "Name", "Symbol", "Price"]);
                foreach ($data["data"] as $crypto) {
                    $currency = new Currency(
                        $crypto["name"],
                        $crypto["symbol"],
                        $crypto["cmc_rank"],
                        $crypto["quote"]["USD"]["price"]
                    );
                    $table->addRow([
                        $currency->getRank(),
                        $currency->getName(),
                        $currency->getSymbol(),
                        $currency->getPrice()
                    ]);
                }
                $table->render();
            } else {
                exit ("error getting data");
            }
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function search(string $symbol): void
    {
        try {
            $data = $this->apiClient->getSymbol($symbol, 'USD');
            if (isset($data["data"])) {
                $crypto = $data["data"][$symbol];
                $currency = new Currency(
                    $crypto["name"],
                    $crypto["symbol"],
                    $crypto["cmc_rank"],
                    $crypto["quote"]["USD"]["price"]
                );
                $output = new ConsoleOutput();
                $table = new Table($output);
                $table->setHeaders(["Rank", "Name", "Symbol", "Price"]);
                $table->addRow([
                    $currency->getRank(),
                    $currency->getName(),
                    $currency->getSymbol(),
                    $currency->getPrice()
                ]);
                $table->render();
            } else {
                exit ("error getting data");
            }
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    private function getBalance(): float
    {
        $result = $this->db->query("SELECT amount FROM balance LIMIT 1");
        $row = $result->fetchArray();
        return $row["amount"];
    }

    private function updateBalance(float $amount): void
    {
        $this->db->exec("UPDATE balance SET amount = $amount WHERE id = 1;");
    }

    private function getTransactions(): array
    {
        $result = $this->db->query("SELECT * FROM transactions");
        $transactions = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $transactions[] = $row;
        }
        return $transactions;
    }

    private function saveTransactions(array $transaction): void
    {
        $save = $this->db->prepare("INSERT INTO transactions (type, symbol, amount, price, value, time) 
VALUES (:type, :symbol, :amount, :price, :value, :time)");
        $save->bindValue(':type', $transaction['type']);
        $save->bindValue(':symbol', $transaction['symbol']);
        $save->bindValue(':amount', $transaction['amount'], SQLITE3_FLOAT);
        $save->bindValue(':price', $transaction['price'], SQLITE3_FLOAT);
        $save->bindValue(':value', $transaction['value'], SQLITE3_FLOAT);
        $save->bindValue(':time', $transaction['time']);
        $save->execute();
    }

    public function purchase(string $symbol): void
    {
        try {
            $data = $this->apiClient->getSymbol($symbol, "USD");
            if (isset($data['data'][$symbol])) {
                $amount = (float)readline("Enter amount of $symbol to buy ");
                if ($amount <= 0) {
                    echo "Enter positive amount " . PHP_EOL;
                    return;
                }
                $price = $data["data"][$symbol]["quote"]["USD"]["price"];
                $cost = $price * $amount;
                $balance = $this->getBalance();
                if ($balance < $cost) {
                    echo "Insufficient funds to buy $amount $symbol " . PHP_EOL;
                    return;
                }
                $this->updateBalance($balance - $cost);
                $this->saveTransactions([
                    'type' => 'purchase',
                    'symbol' => $symbol,
                    'amount' => $amount,
                    'price' => $price,
                    'value' => $cost,
                    'time' => date("Y-m-d H:i:s")
                ]);
                echo "Purchased $amount $symbol for \$$cost" . PHP_EOL;
            } else {
                echo $symbol . " not found" . PHP_EOL;
            }
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function sell(string $symbol): void
    {
        try {
            $data = $this->apiClient->getSymbol($symbol, "USD");
            if (isset($data['data'][$symbol])) {
                $amount = (float)readline("Enter amount of $symbol to sell ");
                if ($amount <= 0) {
                    echo "Enter positive amount " . PHP_EOL;
                    return;
                }
                $price = $data["data"][$symbol]["quote"]["USD"]["price"];
                $value = $price * $amount;
                $bought = 0;
                $sold = 0;
                $transactions = $this->getTransactions();
                foreach ($transactions as $transaction) {
                    if ($transaction['type'] === "purchase" && $transaction['symbol'] === $symbol) {
                        $bought += $transaction['amount'];
                    } elseif ($transaction['type'] === "sell" && $transaction['symbol'] === $symbol) {
                        $sold += $transaction['amount'];
                    }
                }
                $availableAmount = $bought - $sold;
                if ($amount > $availableAmount) {
                    echo "Insufficient amount of $symbol to sell " . PHP_EOL;
                    return;
                }
                $this->updateBalance($this->getBalance() + $value);
                $this->saveTransactions([
                    'type' => 'sell',
                    'symbol' => $symbol,
                    'amount' => $amount,
                    'price' => $price,
                    'value' => $value,
                    'time' => date('Y-m-d H:i:s')
                ]);
                echo "Sold $amount $symbol for \$$value" . PHP_EOL;
            } else {
                echo $symbol . " not found" . PHP_EOL;
            }
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function displayWallet(): void
    {
        $balance = $this->getBalance();
        echo "Current balance is $" . $balance . PHP_EOL;
        $holding = [];
        $transactions = $this->getTransactions();
        foreach ($transactions as $transaction) {
            $symbol = $transaction['symbol'];
            if (!isset($holding[$symbol])) {
                $holding[$symbol] = 0;
            }
            if ($transaction['type'] === 'purchase') {
                $holding[$symbol] += $transaction['amount'];
            } elseif ($transaction['type'] === "sell") {
                $holding[$symbol] -= $transaction['amount'];
            }
        }
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(["Symbol", "Amount"]);
        foreach ($holding as $symbol => $amount) {
            if ($amount > 0) {
                $table->addRow([$symbol, $amount]);
            }
        }
        $table->render();
    }

    public function displayTransactions(): void
    {
        $transactions = $this->getTransactions();
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table->setHeaders(["Type", "Symbol", "Amount", "Price", "Value", "Time"]);
        foreach ($transactions as $transaction) {
            $table->addRow([
                ucfirst($transaction["type"]),
                $transaction['symbol'],
                $transaction['amount'],
                $transaction['price'],
                $transaction['value'],
                $transaction['time']
            ]);
        }
        $table->render();
    }
}