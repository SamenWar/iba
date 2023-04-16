<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

require __DIR__ . 'vendor\autoload.php';

$app = AppFactory::create();

// Замените этими значениями
$encryption_key = hex2bin('103627fe30367872abf21044c57e7b5e57e9e724bfbaa1f3829f1604e5d575bc');

$app->post('/eamgiiki38nwu8mjo', function (Request $request, Response $response) use ($encryption_key) {
    // Получаем данные из запроса
    $data = $request->getParsedBody();
    $encrypted_data = $data['value'];
    $external_user_id = $request->getAttribute('externaluserid');

    // Расшифровываем данные
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($encrypted_data, 0, $ivlen);
    $encrypted_data = substr($encrypted_data, $ivlen);
    $decrypted_data = openssl_decrypt($encrypted_data, $cipher, $encryption_key, 0, $iv);
// Преобразуйте расшифрованный JSON-строку в ассоциативный массив
    $decoded_json = json_decode($decrypted_data, true);

// Извлеките значения APP_ID и ONESIGNAL_API_KEY из массива
    $APP_ID = $decoded_json['app_id'];
    $ONESIGNAL_API_KEY = $decoded_json['onesignal_api_key'];

    // Преобразуем расшифрованные данные в массив
    $json_data = json_decode($decrypted_data, true);
    $campaign_group_name = $json_data['campaign_group_name'];

    // Формируем ответ
    $response_data = ['campaign' => $campaign_group_name];
    $response_json = json_encode($response_data);

    // Кодируем ответ в Base64
    $encoded_response = base64_encode($response_json);

    // Записываем ответ в тело ответа и устанавливаем заголовок Content-Type
    $response->getBody()->write($encoded_response);
    $response = $response->withHeader('Content-Type', 'application/json');

    // Ждем 20 секунд
    sleep(20);

    // Отправляем запрос PUT в OneSignal для обновления тега
    $client = new Client();
    $url = "https://onesignal.com/api/v1/apps/$APP_ID/users/$external_user_id";
    $headers = [
        'Content-Type' => 'application/json',
        'accept' => 'text/plain',
        'Authorization' => "Basic $ONESIGNAL_API_KEY",
    ];
    $key2 = explode('_', $campaign_group_name)[0];
    $body = json_encode([
        'tags' => [
            'key2' => $key2,
        ],
    ]);

    // Отправляем запрос PUT в OneSignal
    $client->put($url, [
        'headers' => $headers,
        'body' => $body,
    ]);

    return $response;
});

$app->run();
