<?php
/**
 * ��� ������������� ����
 * ���� ������� ���� ��������, �� ������� ����� ���������� $_SESSION['quasiform']['key']
 */
$key = $modx->getOption('key', $scriptProperties, false);
/**
 * ����� �������
 */
$debug = $modx->getOption('debug', $scriptProperties, false);

/**
 * ������� �������� �� ������
 */
if (strlen($key)) {
    if (isset($_SESSION['quasiform'])) {
        if (isset($_SESSION['quasiform'][$key])) {
            return $_SESSION['quasiform'][$key];
        }
    }
    return '';
}

$response = array(
	'errors' => array(),
	'success' => false,
	'message' => '',
	'messages' => array(),
	'placeholders' => $modx->getOption('placeholders', $scriptProperties, array()),
);

/**
 * ��������� ������������� � ������
 */
foreach ($response['placeholders'] as $placeholderKey => &$placeholderValue) {
    /* ���� ����� ��������� ������ ��������� �������� ������������� */
    if (is_string($placeholderValue)) {
        $_SESSION['quasiform'][$placeholderKey] = $placeholderValue;
    }
}

$response['success'] = true;
return $response;