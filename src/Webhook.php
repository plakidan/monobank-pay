<?php


namespace MonoPay;


class Webhook
{
    private string $publicKeyBase64;
    private string $xSignBase64;

    /**
     * Класс для верифікації даних з вебхука
     * @param Client $client
     * @param string|null $publicKeyBase64 Публічний ключ, кешований раніше
     * @param string|null $xSignBase64 Підпис, що приходить в заголовку X-Sign разом із вебхуком. Параметр не обов'язковий. Якщо його не передати, бібліотека сама спробує отримати його з хедеру
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function __construct(\MonoPay\Client $client, string $publicKeyBase64=null, string $xSignBase64=null)
    {
        if(!$publicKeyBase64){
            $publicKeyBase64 = $client->getPublicKey();
        }
        if(empty($publicKeyBase64)){
            throw new \Exception('Cannot retrieve public key');
        }
        $this->publicKeyBase64 = $publicKeyBase64;
        if(!empty($xSignBase64)){
            $this->xSignBase64 = $xSignBase64;
        }elseif(!empty($_SERVER['HTTP_X_SIGN'])){
            $this->xSignBase64 = $_SERVER['HTTP_X_SIGN'];
        }else{
            throw new \Exception('Cannot retrieve X-Sign header value');
        }
    }

    /**
     * Перевіряє чи можна довіряти даним з вебхуку
     * @param string|null $requestBody Тіло запиту. Зазвичай це json body вхідного запиту який можна отримати через функцію file_get_contents('php://input')
     * @return bool Чи коректні вхідні дані
     */
    public function verify(string $requestBody=null): bool
    {
        if(empty($requestBody)){
            $requestBody = file_get_contents('php://input');
        }
        $publicKey = \EllipticCurve\PublicKey::fromPem(base64_decode($this->publicKeyBase64));
        $signature = \EllipticCurve\Signature::fromBase64($this->xSignBase64);

        return \EllipticCurve\Ecdsa::verify($requestBody, $signature, $publicKey);
    }
}
