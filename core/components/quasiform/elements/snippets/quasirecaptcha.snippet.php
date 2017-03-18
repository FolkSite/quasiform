<?php
$messageError = $modx->getOption('messageError', $scriptProperties, '�� �����');

$response = array(
	'errors' => array(),
	'success' => false,
	'message' => '',
	'messages' => array(),
);

/**
 * ����
 */
$key = $modx->getOption('key', $scriptProperties, false);
/**
 * ��������� ����
 */
$secret = $modx->getOption('secret', $scriptProperties, false);
/**
 * ����� �������
 * ���� �������, �� � ������ ������ ������������ ���������� ����������
 */
$debug = $modx->getOption('debug', $scriptProperties, false);

if (!function_exists('sendRecaptchaRequest')) {
	/**
	 * ������� �������� ������� �� ����������� reCAPTCHA
	 */
	function sendRecaptchaRequest($secret, $response, $remoteip) {
		$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			"secret"   => $secret,
			"response" => $response,
			"remoteip" => $remoteip,
		));
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}

$googleResponse = sendRecaptchaRequest($secret, $_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
if ($debug) {
	$modx->log(modX::LOG_LEVEL_ERROR, 'google response: '.$googleResponse);
}
$googleResponse = $modx->fromJSON($googleResponse);
if ($debug) {
	$modx->log(modX::LOG_LEVEL_ERROR, 'google array response: '.print_r($googleResponse, true));
}
if (is_array($googleResponse) && $googleResponse['success']) {
	$response['success'] = true;
} else {
	$response['errors'][] = $messageError;
}

return $response;