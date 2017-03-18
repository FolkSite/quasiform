<?php
$debug = $modx->getOption('debug', $scriptProperties, false);

$response = [
	'errors' => [],
	'messages' => $modx->getOption('messages', $scriptProperties, []),
	'placeholders' => $modx->getOption('placeholders', $scriptProperties, []),
	'success' => false,
];

/**
 * ������ ������
 */
$emailTpl = $modx->getOption('emailTpl', $scriptProperties, false);
/**
 * ������ ������, ������������� ����, ��� �������� �����
 */
$emailTplFeedback = $modx->getOption('emailTplFeedback', $scriptProperties, false);
/**
 * ������ ����������� ����� �������
 */
$emailTo = $modx->getOption('emailTo', $scriptProperties, $modx->getOption('emailsender'));
/**
 * ������ ����������� ����� �������
 */
$emailToFeedback = $modx->getOption('email', $_POST, '');
/**
 * ���� ������
 */
$emailSubject = $modx->getOption('emailSubject', $scriptProperties, false);
/**
 * ���� ������, ������������� ����, ��� �������� �����
 */
$emailSubjectFeedback = $modx->getOption('emailSubjectFeedback', $scriptProperties, $emailSubject);
/**
 * ����� ����������� ����� �����������
 */
$emailSenderEmail = $modx->getOption('emailSenderEmail', $scriptProperties, $modx->getOption('emailsender'));
/**
 * ��� �����������
 */
$emailSenderName = $modx->getOption('emailSenderName', $scriptProperties, $modx->getOption('site_name'));
/**
 * ������������ ��� �������� � ������ ������
 */
$placeholders = $modx->getOption('placeholders', $scriptProperties, []);
$placeholders['subject'] = $emailSubject;

/**
 * ���������� � �������
 */
$placeholderServer = [];
if (isset($_SERVER) && is_array($_SERVER)) {
    foreach ($_SERVER as $key => $value) {
        if (is_string($value)) {
            $placeholderServer[$key] = $value;
        }
    }
}
$placeholders['quasiform']['server'] = $placeholderServer;
$placeholders['quasiform']['serverArray'] = print_r($placeholderServer, true);

/**
 * ���� ������ ����� ��������� ����������
 */
if (empty($emailTo)) {
	if ($debug) {
		$modx->log(modX::LOG_LEVEL_ERROR, 'quasiEmail: ������ ����� ��������� ����������');
	}
	$response['errors'][] = '������ ����� ��������� ����������';
	return $response;
}
/**
 * ���� ������ ����� ��������� �����������
 */
if (empty($emailSenderEmail)) {
	if ($debug) {
		$modx->log(modX::LOG_LEVEL_ERROR, 'quasiEmail: ������ ����� ��������� �����������');
	}
	$response['errors'][] = '������ ����� ��������� �����������';
	return $response;
}



/**
 * ���� ��� ������, �� ������� �������� ������
 */
if (!count($response['errors'])) {
	/**
	 * �������� ��������� ������
	 */
	$messageText = (!empty($emailTpl)) ? $modx->getChunk($emailTpl, $placeholders) : '';
    $modx->getService('mail', 'mail.modPHPMailer');
    $modx->mail->setHTML(true);
    $modx->mail->set(modMail::MAIL_BODY, $messageText);
    $modx->mail->set(modMail::MAIL_FROM, $emailSenderEmail);
    $modx->mail->set(modMail::MAIL_FROM_NAME, $emailSenderName);
    $modx->mail->set(modMail::MAIL_SUBJECT, $emailSubject);
    $emails = explode(',', $emailTo);
    if (is_array($emails)) {
	    foreach ($emails as $email) {
	    	$email = trim($email);
	    	if (!empty($email)) {
	    		$modx->mail->address('to', $email);
	    	}
	    }
    }
    $modx->mail->address('reply-to', $emailSenderEmail);
    if ($modx->mail->send()) {
        $response['success'] = true;
		$response['messages'][] = '��������� ������� ����������';
		/**
		 * ���� �������� ������ ���������� �������, �� ������������ �������������� � ������������ �����
		 */
    } else {
 		if ($debug) {
			$modx->log(modX::LOG_LEVEL_ERROR, 'quasiEmail: �� ������� ��������� �������� ������');
		}
		$response['errors'][] = '������ ��� �������� ������';
    }
}

return $response;