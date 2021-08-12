<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '_loader.php';
$db = Database::getInstance();
//var_dump($db->query("show tables;"));


$app = new Slim\App();

/**
 * Get all entered transactions
 */
$app->get('/transactions', function (Request $request, Response $response, array $args){
    $database = Database::getInstance();
    $database->orderBy('transaction_time','desc');

    $return = [];

    foreach($database->get('transactions') as $transaction){
        $time = new DateTime($transaction['transaction_time']);
        $time->setTimezone(new DateTimeZone('Europe/Zurich'));
        $return[] = [
            'id' => $transaction['id'],
            'symbol' => $transaction['crypto_symbol'],
            'time' => $time->format('c'),
            'type' => $transaction['transaction_type'],
            'amount' => $transaction['amount']
        ];
    }

    $response->getBody()->write(json_encode(['data' => $return]));
    $response->withHeader('Content-Type','application/json');
    return $response->withStatus(200);

});

$app->get('/portfolio', function(Request $request, Response $response, array $args){
    $database = Database::getInstance();
    $return = [];

    $curl = curl_init();


    foreach($database->get('portfolio') as $balance){
        curl_setopt($curl, CURLOPT_URL, 'https://min-api.cryptocompare.com/data/price?fsym='.$balance['crypto_symbol'].'&tsyms=CHF');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $fiat = 0;
        if(json_encode(json_decode($output, true)) === $output){
            $fiatData = json_decode($output, true);

            // Calculate the current price of the symbol. Save data in int (Rappen), so no
            // weird conversion and calculation has to happen.
            $fiatExchange = explode('.',(string)$fiatData['CHF']);
            $fiat = (int)$fiatExchange[0]*100;
            if(isset($fiatExchange[1])) {
                if(strlen($fiatExchange[1]) == 1){
                    $fiat += (int)$fiatExchange[1]*10;
                } else {
                    $fiat += (int)$fiatExchange[1];
                }
            }

            $fiat *= $balance['balance'];
            $fiat /= 1000000;
            $fiat = (int)round($fiat);
        }

        $return[] = [
            'symbol' => $balance['crypto_symbol'],
            'balance' => $balance['balance'],
            'fiat' => $fiat
        ];
    }
    curl_close($curl);


    $response->getBody()->write(json_encode(['data' => $return]));
    $response->withHeader('Content-Type','application/json');
    return $response->withStatus(200);

});


/**
 * Add a new transaction to database
 */
$app->post('/transaction', function(Request $request, Response $response, array $args){
    $database = Database::getInstance();
    $inputData = InputParser::parseRequest($request);

    $errors = [];

    // input validation

    $cryptoSymbol = $inputData['symbol'] ?? false;
    $transactionTime = $inputData['time'] ?? false;
    $transactionType = $inputData['type'] ?? false;
    $amount = $inputData['amount'] ?? false;

    if(!$cryptoSymbol || !$transactionTime ||! $transactionType || !$amount){
        $errors[] = 'Please specify all fields';
    } else {

        if (!in_array($cryptoSymbol, ['ETH', 'BTC', 'XRP'])) {
            $errors[] = 'Unexpected crypto symbol';
        }

        try {
            $transactionTime = new DateTime($transactionTime);
            $transactionTime->setTimezone(new DateTimeZone('Europe/Zurich'));
        } catch (Exception $e) {
            $errors[] = 'Invalid Date format';
        }

        if (!in_array($transactionType, ['BUY', 'SELL'])) {
            $errors[] = 'Unexpected transaction type';
        }

        if ($amount < 0) {
            $errors[] = 'Amount cannot be negative';
        }
    }
    if(count($errors) >  0){
        $response->getBody()->write(json_encode(['errors' => $errors]));
        return $response->withStatus(400);
    }

    // TODO: get price of symbol at specified time and also save it in DB

    $insertOk = $database->insert('transactions', [
        'crypto_symbol' => $cryptoSymbol,
        'transaction_time' => $transactionTime->format("Y-m-d H:i:s"),
        'transaction_type' => $transactionType,
        'amount' => $amount
    ]);


    // If insert didn't work, throw errors to frontend
    if(!$insertOk){
        $response->getBody()->write(json_encode(['errors' => 'Unexpected Error in insert operation']));
        return $response->withStatus(500);
    }

    // If insert worked, get the ID of this insert and post it back to the frontend
    $newId = $database->getInsertId();
    $response->getBody()->write(json_encode(['data' => ['id' => $newId]]));
    $response->withHeader('Content-Type','application/json');
    return $response->withStatus(201);
});


$app->run();