<?php
class CRest
{
    const CREST_CURRENT_VERSION = '1.0';

    public static function call($method, $params = [])
    {
        $postData = http_build_query($params);
        return self::execute($method, $postData);
    }

    public static function callBatch($batch)
    {
        $arCmd = [];
        foreach ($batch as $key => $data) {
            $arCmd[$key] = $data['method'] . '?' . http_build_query($data['params']);
        }
        $postData = http_build_query(['cmd' => $arCmd, 'halt' => 0]);
        return self::execute('batch', $postData);
    }

    private static function execute($method, $postData)
    {
        $authId = $_REQUEST['AUTH_ID'] ?? $_REQUEST['auth']['access_token'] ?? '';
        $domain = $_REQUEST['DOMAIN'] ?? $_REQUEST['auth']['domain'] ?? '';

        if (!$authId || !$domain) {
            return ['error' => 'NO_AUTH', 'error_description' => 'Authorization data not found in request'];
        }

        $url = 'https://' . $domain . '/rest/' . $method . '.json';
        $postData .= '&auth=' . $authId;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_FOLLOWLOCATION => 1, // Важно для редиректов
            CURLOPT_TIMEOUT => 10        // Таймаут
        ]);

        $result = curl_exec($curl);
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        curl_close($curl);

        if ($errno) {
            return ['error' => 'CURL_ERROR', 'error_description' => $error];
        }

        $jsonResult = json_decode($result, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Если вернулся не JSON (например HTML ошибки), возвращаем как есть для отладки
            return ['error' => 'JSON_PARSE_ERROR', 'result_raw' => $result];
        }

        return $jsonResult;
    }
}
?>