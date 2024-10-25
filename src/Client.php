<?php


namespace MonoPay;

class Client extends RequestBuilder
{
    private ?string $merchantId;
    private ?string $merchantName;
    public string $apiEndpoint = 'https://api.monobank.ua/';
    private \GuzzleHttp\Client $httpClient;

    /**
     * Створює клієнт з ключем для запитів до серверу Mono і отримує дані про мерчант
     * @param string $token Токен з особистого кабінету https://fop.monobank.ua/ або тестовий токен з https://api.monobank.ua/
     * @param array $custom_headers Додаткові кастомні хедери які хочете передати - масив масивів ([ключ => значення])
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @link https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1details/get Так отримуються деталі мерчанту
     */
    public function __construct(string $token, array $custom_headers = [])
    {
        $headers = [
            'X-Token' => $token,
        ];
        if($custom_headers){
            $headers = array_merge($custom_headers, $headers);
        }

        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->apiEndpoint,
            \GuzzleHttp\RequestOptions::TIMEOUT => 10,
            \GuzzleHttp\RequestOptions::HEADERS => $headers,
            \GuzzleHttp\RequestOptions::HTTP_ERRORS => false,
        ]);
        $response = $this->httpClient->request('GET', '/api/merchant/details');
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        if ($response->getStatusCode() == 200) {
            if ($data && isset($data['merchantId']) && isset($data['merchantName'])) {
                $this->merchantId = $data['merchantId'];
                $this->merchantName = $data['merchantName'];
            } else {
                throw new \Exception('Cannot decode json response from Mono', 500);
            }
        } else {
            throw new \Exception($data['errorDescription'] ?? 'Unknown error response: ' . $json, $response->getStatusCode());
        }
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getMerchantName(): string
    {
        return $this->merchantName;
    }

    public function getClient():  \GuzzleHttp\Client
    {
        return $this->httpClient;
    }

    /**
     * Відкритий ключ для верифікації підписів
     * Отримання відкритого ключа для перевірки підпису, який включено у вебхуки. Ключ можна кешувати і робити запит на отримання нового, коли верифікація підпису з поточним ключем перестане працювати. Кожного разу робити запит на отримання ключа не треба
     * @link https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1pubkey/get
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getPublicKey(): string
    {
        $response = $this->getClient()->request('GET','/api/merchant/pubkey');
        $data = $this->getDataFromGuzzleResponse($response);
        if(!isset($data['key'])){
            throw new \Exception('Invalid response from Mono API',500);
        }
        return $data['key'];
    }

    /**
     * Дані мерчанта
     * @link https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1details/get
     * @return array Масив з ключами merchantId та merchantName
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function getMerchant(): array
    {
        $response = $this->getClient()->request('GET','/api/merchant/details');
        return $this->getDataFromGuzzleResponse($response);
    }
}