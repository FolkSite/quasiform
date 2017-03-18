<?php
/**
 * ����� �������
 * ��������� ������������ ������ � ������� /sms/%H%i%s.txt
 */
$debug = $modx->getOption('debug', $scriptProperties, false);

$response = array(
	'success' => false,
	'errors' => array(),
	'messages' => $modx->getOption('messages', $scriptProperties, array()),
	'placeholders' => $modx->getOption('placeholders', $scriptProperties, array()),
);
/**
 * API-����
 */
$key = $modx->getOption('key', $scriptProperties, false);
/**
 * ����� ����������
 */
$to = $modx->getOption('to', $scriptProperties, false);
/**
 * ����� ���������
 */
$text = $modx->getOption('text', $scriptProperties, false);
/**
 * ������, ������������ ������� �� �������� ���
 */
$query = 'http://sms.ru/sms/send?api_id='.$key.'&to='.$to.'&text='.$text;

if (!function_exists('sendSms')) {
	function sendSms($key, $to, $text, $debug = false) {
		if ($debug) {
			return (bool)file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sms/'.$to.'.txt', $text);
		} else {
			$ch = curl_init('http://sms.ru/sms/send');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				"api_id"		=>	$key,
				"to"			=>	$to,
				//"text"		=>	iconv("windows-1251', 'utf-8', $text")
				"text"			=>	$text,
			));
			$data = curl_exec($ch);
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sms/'.$to.'_answer.txt', $data);
			curl_close($ch);
			return true;
		}
	}
}

if (empty($key)) {
	$response['errors'][] = '������ �����������';
}
if (empty($to)) {
	$response['errors'][] = '������ ����� ��������';
}
if (empty($text)) {
	$response['errors'][] = '������ ���������';
}
if (strlen($text) > 70) {
	$response['errors'][] = '������������ ����� ��������� � 70 ��������';
}

if (!count($response['errors'])) {
	if (sendSms($key, $to, $text, $debug)) {
	  	$response['success'] = true;
	} else {
	  	$response['errors'][] = '�� ������� ��������� ���';
	}
} else {

}

return $response;