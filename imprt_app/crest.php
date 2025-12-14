<?php
class CRest
{
    const CREST_CURRENT_VERSION = '1.3';

    public static function call($method, $params = [])
    {
        return self::execute($method, $params);
    }

    public static function callBatch($batch)
    {
        $arCmd = [];
        foreach ($batch as $key => $data) {
            $arCmd[$key] = $data['method'] . '?' . http_build_query($data['params']);
        }
        return self::execute('batch', ['cmd' => $arCmd, 'halt' => 0]);
    }

    private static function execute($method, $params)
    {
        $authId = $_REQUEST['AUTH_ID'] ?? $_REQUEST['auth']['access_token'] ?? '';
        $domain = $_REQUEST['DOMAIN'] ?? $_REQUEST['auth']['domain'] ?? '';

        if (!$authId || !$domain) {
            // Если нет авторизации, попробуем взять из констант (если используются вебхуки)
            if (defined('C_REST_WEB_HOOK_URL')) {
                 $url = C_REST_WEB_HOOK_URL . $method . '.json';
            } else {
                 return ['error' => 'NO_AUTH', 'error_description' => 'Authorization data not found'];
            }
        } else {
            $params['auth'] = $authId;
            $url = 'https://' . $domain . '/rest/' . $method . '.json';
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_POST => 1,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            // JSON - единственный способ корректно передать сложные фильтры
            CURLOPT_POSTFIELDS => json_encode($params), 
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 30
        ]);

        $result = curl_exec($curl);
        curl_close($curl);
        $jsonResult = json_decode($result, true);

        return $jsonResult ?: ['error' => 'CURL_ERROR', 'result_raw' => $result];
    }
}
?>