<?php
/**
 * ���������
 * @param fields ���� � ������� JSON
 * @param hooks �������� � ������� JSON, ���������� �� ����� ���������� �������� �������� 
 */
$fields = $modx->fromJSON($modx->getOption('fields', $scriptProperties, false));
$messageSuccess = $modx->getOption('messageSuccess', $scriptProperties, '���� ��������� ������� ����������. �������.');
$messageError = $modx->getOption('messageError', $scriptProperties, '����� ��������� � ��������. ��������� �� � ��������� �����.');
$hooks = $modx->fromJSON($modx->getOption('hooks', $scriptProperties, false));
$debug = $modx->getOption('debug', $scriptProperties, false);

$placeholders = array();

$post = $_POST;
/**
 * �����, ������������ � ������� JSON
 */
$response = [
	'author' => '������ ������� � quasi-art.ru',
	'errors' => [],
	'field_errors' => [],
	'messages' => [],
	'success' => false,
];
$errorsHTML = '';

/**
 * ������ ��� ����� � ��������� ��������
 */
if (is_array($fields)) {
	foreach ($fields as $fieldName => &$fieldProperties) {
		if ($debug) {
			$response['debug'][] = 'field: '.$fieldName;
		}
		/**
		 * ������� ������ �������� ������ ����
		 */
		$fieldLabel = (isset($fieldProperties['label'])) ? $fieldProperties['label'] : '';
		$fieldValue = (isset($post[$fieldName])) ? $post[$fieldName] : '';
		foreach ($fieldProperties as $fieldPropertyName => &$fieldPropertyValue) {
			if ($debug) {
				$response['debug'][] = 'fieldProperty: '.$fieldPropertyValue;
			}
			switch ($fieldPropertyName) {
				/**
				 * ����������
				 */
				case 'blank':
				    /**
				     * ���� ������ ���� ������
				     */
					if (!empty($fieldValue)) {
						$response['errors'][] = '������ ���������� �����';
					}
					break;
				case 'email':
				    /**
				     * ���� ������ ���� ������� ���������� �����
				     * ������ �������� �� ����������� (����� ������������� � required)
				     */
					if (!empty($fieldValue)) {
						if (!filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
							$response['field_errors'][$fieldName][] = '���� �'.$fieldLabel.'� ������ ���� ������� ����������� �����';
						}
					}
				/**
				 * �������� ���� ������ ���� ����� ������-�� ������������ ��������
				 * "equal":"2015"
				 */
				case 'equal':
				case 'equals':
					if ($fieldValue != $fieldPropertyValue) {
						$response['errors'][] = '������ ���������� �����';
					}

					break;
				case 'length':
					if (strlen($fieldValue) != strlen($fieldPropertyValue)) {
					  	$response['field_errors'][$fieldName][] = '���������� �������� ��� ���� �'.$fieldLabel.'� ������ ���� ����� '.$fieldPropertyValue;
					}
					break;
				case 'minlength':
					if (strlen($fieldValue) < $fieldPropertyValue) {
						$response['field_errors'][$fieldName][] = '���������� �������� ��� ���� �'.$fieldLabel.'� ('.strlen($fieldValue).') ������ ���� �� ����� '.$fieldPropertyValue;
					}
					break;
				case 'maxlength':
					if (strlen($fieldValue) > $fieldPropertyValue) {
						$response['field_errors'][$fieldName][] = '��������� ���������� �������� ��� ���� �'.$fieldLabel.'�: '.strlen($fieldValue).'/'.$fieldPropertyValue;
					}
					break;
				case 'required':
					if (empty($fieldValue) && $fieldPropertyValue) {
						$response['field_errors'][$fieldName][] = '���� �'.$fieldLabel.'� ����������� ��� ����������';
					}
					break;
				/**
				 * ������������
				 */
				case 'strip_tags':
					$fieldValue = ($fieldPropertyValue) ? strip_tags($fieldValue) : $fieldValue;
					break;
				case 'trim':
					$fieldValue = ($fieldPropertyValue) ? trim($fieldValue) : $fieldValue;
					break;
				case 'htmlentities':
					$fieldValue = ($fieldPropertyValue) ? htmlentities($fieldValue) : $fieldValue;
					break;
				default:
					break;
			}
		}
	
		/**
		 * ��������� ������������� ��� �������� � ������ ������
		 */
		$placeholders[$fieldName] = $fieldValue;
	}
} else {
	if ($debug) {
		$response['debug'][] = 'fields is not an array ('.gettype($fields).')';
	}
}

// ���� ���� ������ ���������, �� ���������� �������-��������
if (!count($response['errors']) && !count($response['field_errors'])) {
	// ������ ��������-���������, ������� ������ ����������� ����� ��������� �����
	if (is_array($hooks)) {
		foreach ($hooks as $hookName => &$hookProperties) {
			if ($debug) {
				$response['hooks'][] = $hookName;
			}
			/**
			 * ��������� ��� �������� � ������-�������
			 */
			if (is_array($hookProperties)) {
				$properties = array_merge($hookProperties, array('placeholders' => $placeholders));
			} else {
				$properties = array('placeholders' => $placeholders);
			}
			if ($debug) {
				$response['properties'][] = $properties;
			}
			/**
			 * ����� �������-��������
			 */
			$hookResponse = $modx->runSnippet($hookName, $properties);
			if (is_array($hookResponse)) {
				if (is_array($hookResponse['errors'])) {
					foreach ($hookResponse['errors'] as &$responseError) {
						$response['errors'][] = $responseError;
					}
				}
				if (is_array($hookResponse['messages'])) {
					foreach ($hookResponse['messages'] as &$responseMessage) {
						$response['messages'][] = $responseMessage;
					}
				}
				if (is_array($hookResponse['placeholders'])) {
					foreach ($hookResponse['placeholders'] as $placeholderName => &$placeholderValue) {
						$placeholders[$placeholderName] = $placeholderValue;
					}
				}
				/**
				 * ���� ������-������� ���������� �������, ������������ ���������� ����������� ��������
				 */
				if (!$hookResponse['success']) {
					if ($debug) {
						$response['debug'][] = $hookName.' is fail';
					}
					break;
				}
			} else {
				if ($debug) {
					$response['debug'][] = $hookName.' is failed';
					$response[$hookName]['response'] = $hookResponse;
				}
				break;
			}
	
		}
	} else {
		$response['errors'][] = '�������� ������ ������ �������� quasiForm';
	}
}

/**
 * ���� ������� ���� ��������� �������
 */
if (!count($response['errors']) && !count($response['field_errors'])) {
	$response['success'] = true;
}

if ($response['success']) {
	$response['messages'][] = $messageSuccess;
} else {
	$response['errors'][] = $messageError;
}

// ��������� ������ ������� � JSON-�������
return json_encode($response, JSON_UNESCAPED_UNICODE);