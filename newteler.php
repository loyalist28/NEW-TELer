<?php
/**
 * Простой класс-обёртка для работы с API NEW-TEL.
 *
 * Для работы требуется создать экземпляр класса, передав в конструктор ключи, сформированные в личном кабинете кабинете системы NEW-TEL.
 * В данный момент реализован вызов метода "call-password/start-password-call" (авторизация по звонку)
 * через метод startCallPassword(). Обращение к остальному функционалу API NEW-TEL возможно через
 * универсальный метод sendRequest(). Класс будет дополняться и развиваться по мере необходимости.
 * 
 */
class NewTeler {
    /**
     * @var  string  Ключ доступа к API-серверу NEW-TEL
     */
    private string $newTelAPIKey;

    /**
     * @var  string  Ключ для подписи запросов
     */
    private string $requestsSignKey;

    /**
     * Конструктор класса
     *
     * @param  string  $newTelAPIKey     Ключ доступа к API-серверу NEW-TEL
     *
     * @param  string  $requestsSignKey  Ключ для подписи запросов
     */
    function __construct(string $newTelAPIKey, string $requestsSignKey)
    {
        $this->newTelAPIKey = $newTelAPIKey;
        $this->requestsSignKey = $requestsSignKey;
    }

    /**
     * Универсальный метод отправки запросов к API NEW-TEL
     *
     * @param   string  $methodName          Имя метода API NEW-TEL без ведущего и закрывающего слэша,
     *                                       пример: "company/get-state"
     *
     * @param   array   $params              Массив параметров передаваемых методу API NEW-TEL
     *                                       Необязательный. По-умолчанию - пустой массив
     *
     * @param   bool    $jsonSendFormat      Флаг указывающий в каком формате передавать параметры
     *                                       и какое значение устанавливать в http-заголовок "Content-Type".
     *                                       true - application/json
     *                                       false - application/x-www-form-urlencoded
     *                                       Необязательный. По-умолчанию - true
     *
     * @param   bool    $acceptJsonFormat    Флаг указывающий в каком формате вернуть ответ с сервера
     *                                       true - application/json
     *                                       false - application/xml
     *                                       Необязательный. По-умолчанию - true
     *
     * @return  string                       Возвращает результат работы вызываемого метода API NEW-TEL
     *                                       в заданном параметром $acceptJsonFormat формате
     */
    public function sendRequest(string $methodName, array $params = [], bool $jsonSendFormat = true, bool $acceptJsonFormat = true) : string
    {
        $contentTypeHeader = 'application/json';
        $acceptHeader = 'application/json';
        if($jsonSendFormat) {
            $params = json_encode($params);
        } else {
            $params = http_build_query($params);
            $contentTypeHeader = 'application/x-www-form-urlencoded';
        }
        if(!$acceptJsonFormat) {
            $acceptHeader = 'application/xml';
        }
        $bearerToken = $this->getToken($methodName, $params); // Bearer-токен для авторизации запросов

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $bearerToken,
                'Content-Type: ' . $contentTypeHeader,
                'Accept: ' . $acceptHeader
            ],
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => 'https://api.new-tel.net/' . $methodName,
            CURLOPT_POSTFIELDS => $params
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * Метод вызова авторизации по звонку
     *
     * @param   array   $params              Массив параметров передаваемых методу API NEW-TEL
     *
     * @param   bool    $jsonSendFormat      Флаг указывающий в каком формате передавать параметры
     *                                       и какое значение устанавливать в http-заголовок "Content-Type".
     *                                       true - application/json
     *                                       false - application/x-www-form-urlencoded
     *                                       Необязательный. По-умолчанию - true
     *
     * @param   bool    $acceptJsonFormat    Флаг указывающий в каком формате вернуть ответ с сервера
     *                                       true - application/json
     *                                       false - application/xml
     *                                       Необязательный. По-умолчанию - true
     *
     * @return  string                       Возвращает результат работы метода "call-password/start-password-call"
     *                                       в заданном параметром $acceptJsonFormat формате
     */
    public function startCallPassword(array $params, bool $jsonSendFormat = true, bool $acceptJsonFormat = true) : string
    {
        $result = $this->sendRequest('call-password/start-password-call', $params, $jsonSendFormat, $acceptJsonFormat);

        return $result;
    }

    /**
     * Метод вычисления Bearer-токена для авторизации запроса на сервере NEW-TEL
     *
     * @param   string  $methodName          Имя метода API NEW-TEL без ведущего и закрывающего слэша,
     *                                       пример: "company/get-state"
     *
     * @param   array   $params              Массив параметров передаваемых методу API NEW-TEL
     *                                       Необязательный. По-умолчанию - пустой массив
     *
     * @return  string                       Возвращает 122-х символьную строку, являющуюся Bearer-токеном
     */
    private function getToken($methodName, $params = []) : string
    {
        $time = time();

        return $this->newTelAPIKey . $time . hash('sha256',
            $methodName . "\n" . $time . "\n" . $this->newTelAPIKey . "\n" . $params . "\n" . $this->requestsSignKey);
    }
}
