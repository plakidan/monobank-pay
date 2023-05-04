# Проста бібліотека для [MonobankPay](https://api.monobank.ua/) (MonoPay)
Документація по REST API [тут](https://api.monobank.ua/docs/acquiring.html)

Для ведення запитів вам знадобиться токен з особистого кабінету [https://fop.monobank.ua/](https://fop.monobank.ua/) або тестовий токен з [https://api.monobank.ua/](https://api.monobank.ua/)

Встановити бібліотеку:
```bash
composer require plakidan/monobank-pay
```

### Мінімальні вимоги:
* php >=7.4
* guzzlehttp/guzzle >= 7.0
* starkbank/ecdsa >= 0.0.5

### Приклади використання:
```php
require_once('vendor/autoload.php');

//створили клієнта - через нього запити будуть слатись
$monoClient = new \MonoPay\Client('YOUR_TOKEN_HERE');

//із клієнта можна отримати id та назву мерчанта
echo $monoClient->getMerchantId();
echo $monoClient->getMerchantName();

//для створення платежів створюємо цей об'єкт
$monoPayment = new \MonoPay\Payment($monoClient);

//створення платежу
$invoice = $monoPayment->create(1000,[
           'merchantPaymInfo' => [ //деталі оплати
               'reference' => 'my_shop_order_28142', //Номер чека, замовлення, тощо; визначається мерчантом (вами)
               'destination' => 'Оплата за замовлення #28142', //Призначення платежу
               'basketOrder' => [ //Склад замовлення, використовується для відображення кошика замовлення
                   [
                       'name' => 'Товар1', //Назва товару
                       'qty' => 2, //Кількість
                       'sum' => 500, //Сума у мінімальних одиницях валюти за одиницю товару
                       'icon' => 'https://example.com/images/product1.jpg', //Посилання на зображення товару
                       'unit' => 'уп.', //Назва одиниці вимiру товару
                   ]
               ]
           ],
           'redirectUrl' => 'https://example.com/order-result', //Адреса для повернення (GET) - на цю адресу буде переадресовано користувача після завершення оплати (у разі успіху або помилки)
           'webHookUrl' => 'https://example.com/mono-webhook', //Адреса для CallBack (POST) – на цю адресу буде надіслано дані про стан платежу при кожній зміні статусу. Зміст тіла запиту ідентичний відповіді запиту “перевірки статусу рахунку”
           'validity' => 3600*24*7, //Строк дії в секундах, за замовчуванням рахунок перестає бути дійсним через 24 години
           'paymentType' => 'debit', //debit | hold. Тип операції. Для значення hold термін складає 9 днів. Якщо через 9 днів холд не буде фіналізовано — він скасовується
       ]);
print_r($invoice);

//інформація про платіж
$invoice = $monoPayment->info('2305046jUBEj8WfyaBdB');
print_r($invoice);

//відшкодування
$result = $monoPayment->refund('2305046jUBEj8WfyaBdB');
print_r($result);

//скасування посилання на оплату
$result = $monoPayment->cancel('2305046jUBEj8WfyaBdB');
print_r($result);

//деталі успішної оплати
$invoiceDetails = $monoPayment->successDetails('2305046jUBEj8WfyaBdB');
print_r($invoiceDetails);

//списати заблоковану сумму
//зверніть увагу: списати можна тільки таку самму або меншу сумму яку ви заблокували
$result = $monoPayment->captureHold('2305046jUBEj8WfyaBdB',500);
print_r($result);

//список успішних оплат за останні сутки
$list = $monoPayment->items(time()-60*60*24);
print_r($list);
```

### Отримання вебхуку:
```php
require_once('vendor/autoload.php');

//створили клієнта - через нього запити будуть слатись
$monoClient = new \MonoPay\Client('YOUR_TOKEN_HERE');

//отримання публічного ключа (бажано закешувати)
$publicKey = $monoClient->getPublicKey();

//класс для роботи з вебхуком
$monoWebhook = new \MonoPay\Webhook($monoClient,$publicKey,$_SERVER['HTTP_X_SIGN']);
//отримуємо вхідні дані
$body = file_get_contents('php://input');
//валідуємо дані
if($monoWebhook->verify($body)){
    echo "Ці дані прислав монобанк, можна обробляти";
}else{
    echo "Дані прислав шахрай, ігноруємо";
}
```

#### TODO List:
* Доробити методи стосовно токенізації карт
* Переробити вхідні параметри і вихідні дані на класи з описаними методами
* Попросити в Гороховського баночку пива
