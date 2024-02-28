<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use MiladRahimi\Jwt\Cryptography\Algorithms\Rsa\RS256Verifier;
use MiladRahimi\Jwt\Cryptography\Keys\RsaPublicKey;
use MiladRahimi\Jwt\Parser;

$startTime = microtime(true);

require_once __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if ( ! isset(getallheaders()['Content-Type']) || ! str_starts_with(getallheaders()['Content-Type'],
        'application/json')) {
    http_response_code(415);
    exit;
}

if ( ! isset(getallheaders()['Authorization']) || ! str_starts_with(getallheaders()['Authorization'], 'Bearer')) {
    http_response_code(401);
    exit;
}

//$authToken = str_replace('Bearer ', '', getallheaders()['Authorization']);
//$publicKey = getForgePublicKey($authToken);
//
//if ( ! $publicKey || ! verifyForgeRequest($publicKey, $authToken)) {
//    http_response_code(401);
//    exit;
//}

$requestData = json_decode(file_get_contents('php://input'));
error_log(var_export($requestData, true));

if ( ! isset($requestData->summary, $requestData->description)) {
    http_response_code(400);
    exit;
}

$options               = new QROptions();
$options->outputBase64 = true;
$qrCode                = (new QRCode($options))->render("Issue summary: {$requestData->summary}\n\nDescription: {$requestData->description}");

echo json_encode([
    'qrCode'  => $qrCode,
    'runtime' => microtime(true) - $startTime,
]);


function getForgePublicKey($authToken)
{
    $curl = curl_init('https://forge.cdn.prod.atlassian-dev.net/.well-known/jwks.json');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR    => true,
    ]);

    $jwks = json_decode(curl_exec($curl));
    $jwt  = json_decode(base64_decode(str_replace('_', '/',
        str_replace('-', '+', explode('.', $authToken)[0]))));

    foreach ($jwks->keys as $key) {
        if ($key->kid === $jwt->kid) {
            $filename = tempnam(sys_get_temp_dir(), 'jira-forge-remote-demo');
            file_put_contents($filename, $key->n);
            $publicKey = new RsaPublicKey($filename);
            unlink($filename);

            return $publicKey;
        }
    }

    return null;
}

function verifyForgeRequest($publicKey, $authToken)
{
    $verifier = new RS256Verifier($publicKey);
    $parser   = new Parser($verifier);

    try {
        $parser->verify($authToken);

        return true;
    } catch (Exception $exception) {
        var_dump(get_class($exception));
        die;

        return false;
    }
}
