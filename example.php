<?php

require 'src/KunaApi.php';


$client = new KunaApi([
	'publicKey' => 'Your public (access) key',
	'secretKey' => 'Your secret key'
]);


// Public methods

$result = $client->tickers(KunaApi::MARKET_BTCUAH);
echo "\n\n=== Tickers ===\n";
print_r($result);


$result = $client->orderBook(KunaApi::MARKET_BTCUAH);
echo "\n\n=== Order Book ===\n";
print_r($result);


$result = $client->trades(KunaApi::MARKET_BTCUAH);
echo "\n\n=== Trades ===\n";
print_r($result);



// Private methods

$result = $client->me();
echo "\n\n=== My Account ===\n";
print_r($result);


$result = $client->myOrderList(KunaApi::MARKET_BTCUAH);
echo "\n\n=== My Order list ===\n";
print_r($result);


$result = $client->myTradesList(KunaApi::MARKET_BTCUAH);
echo "\n\n=== My Trades list ===\n";
print_r($result);



$result = $client->createOrder(1.0, 15000, KunaApi::MARKET_BTCUAH, KunaApi::SIDE_BUY);
echo "\n\n=== Create order ===\n";
print_r($result);


$result = $client->createOrderBuy(1.0, 15000, KunaApi::MARKET_BTCUAH);
echo "\n\n=== Create order BUY ===\n";
print_r($result);


$result = $client->createOrderSell(1.0, 15000, KunaApi::MARKET_BTCUAH);
echo "\n\n=== Create order SELL ===\n";
print_r($result);


$result = $client->deleteOrder(395);
echo "\n\n=== Delete order 395: ===\n";
print_r($result);