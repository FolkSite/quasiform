<?php
/**
 * @param string $input �������� �������� ���� ������ �����
 * @param string $dir ������� �������� ������
 * @param string $types ������ ���������� ����� �������
 * @param string $size ������������ ������ ������� �����
 * @param string $mincount ����������� ���������� ������
 * @param string $maxcount ������������ ���������� ������
 * @param string $translit ����� �� �������������� ����� ������
 */
$input = $modx->getOption('field', $scriptProperties, false);
$dir = $modx->getOption('dir', $scriptProperties, false);
$types = $modx->getOption('types', $scriptProperties, false);
$size = $modx->getOption('maxsize', $scriptProperties, false);
$mincount = $modx->getOption('mincount', $scriptProperties, 0);
$maxcount = $modx->getOption('maxcount', $scriptProperties, NULL);
$translit = $modx->getOption('translit', $scriptProperties, false);
$debug = $modx->getOption('debug', $scriptProperties, false);

if ($debug) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
	$modx->log(modX::LOG_LEVEL_ERROR, print_r($scriptProperties, true));
}

/**
 * ����� ��������
 */
$response = array(
	'errors' => array(),
	'success' => false,
	'files' => array(),
	'messages' => array(),
	'placeholders' => array(),
);

if (!function_exists('getFileUploadErrorDescription')) {
	function getFileUploadErrorDescription($code = 0) {
		$output = '';
		
		switch ($code) {
		    case 1:
		        $output = '������ ��������� ����� �������� ����������� ���������� ������, ������� ����� ���������� upload_max_filesize ����������������� ����� php.ini';
		        break;
		    case 2:
		        $output = '������ ������������ ����� �������� �������� MAX_FILE_SIZE, ��������� � HTML-�����';
		        break;
		    case 3:
		        $output = '����������� ���� ��� ������� ������ ��������.';
		        break;
		    case 4:
		        $output = '���� �� ��� ��������.';
		        break;
		    case 6:
		        $output = '����������� ��������� �����.';
		        break;
		    case 7:
		        $output = '�� ������� �������� ���� �� ����.';
		        break;
		    case 8:
		        $output = 'PHP-���������� ���������� �������� �����.';
		        break;
		}
		
		return $output;
	}
}

if (!function_exists('getFileExtension')) {
	/**
	 * ��������� ���������� �����
	 * @param string $filename ��� �����
	 * @param boolean $strtolower �������� ������� � ��������
	 * @return string ���������� �����
	 */
	function getFileExtension($filename, $strtolower = true) {
		if (!is_string($filename)) {
			return '';
		}
		if ($strtolower) {
			$filename = strtolower($filename);
		}
		$filename = explode('.', $filename);
		return (is_array($filename) && count($filename) > 1) ? end($filename) : '';
	}
}

if (!function_exists('genFilename')) {
	/**
	 * ��������� ����� �����
	 * @return string ��� ����� ��� ����������
	 */
	function genFilename() {
		// ������ ���� 1234567890_nu6tFrgh
		return time().'_'.genPassword();
	}
}

if (!function_exists('genPassword')) {
	/**
	 * ��������� ������������������ ��������� �������� ����������� �����
	 * @param integer $length ����� ������
	 * @return string $result ��������� ������
	 */
	function genPassword($length = 8) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$count = mb_strlen($chars);
		for ($i = 0, $result = ''; $i < $length; $i++) {
			$index = rand(0, $count - 1);
			$result .= mb_substr($chars, $index, 1);
		}
		return $result;
	}
}

$filesOriginal = $_FILES[$input];
if ($debug) {
	$modx->log(modX::LOG_LEVEL_ERROR, '�������� ������: '.print_r($_FILES[$input], true));
}

/**
 * �������� ����� �������� ������� ����������� ������
 */
for ($i = 0; $i < count($filesOriginal['name']); $i++) {
    $name = $filesOriginal['name'][$i];
	$type = $filesOriginal['type'][$i];
    $tmp_name = $filesOriginal['tmp_name'][$i];
	$error = $filesOriginal['error'][$i];
	$size = $filesOriginal['size'][$i];
	
	/**
	 * ���� �� ���� ���� �� ���������
	 */
	$modx->log(modX::LOG_LEVEL_ERROR, 'quasiUpload['.$i.']: count = '.count($filesOriginal['name']).'; name: '.$name.' type: '.$type);
	if (count($filesOriginal['name']) == 1 && empty($name) && empty($type) && empty($tmp_name) && $error == 4 && $size == 0) {
	    $modx->log(modX::LOG_LEVEL_ERROR, 'quasiUpload['.$i.'] continue');
	    continue;
	}
	
    $array = array(
		'name' => $filesOriginal['name'][$i],
		'type' => $filesOriginal['type'][$i],
		'tmp_name' => $filesOriginal['tmp_name'][$i],
		'error' => $filesOriginal['error'][$i],
		'size' => $filesOriginal['size'][$i],
	);
	$response['files'][] = $array;
}
/**
 * ���� ������� ������������ ���������� ������, �� ���������� ����������� ������ �� ������
 * ��������� ��������� �����������
 */
if ($maxcount > 0 && count($response['files']) > $maxcount) {
	$response['errors'][] = '�������� ����� ���������� ������ (���������� '.count($response['files']).')';
	if ($debug) {
		$modx->log(modX::LOG_LEVEL_ERROR, '�������� ����� ���������� ������ (���������� '.count($response['files']).')');
	}
	return $response;
}
/**
 * ���� ������� ����������� ���������� ������, �� ���������� ����������� ������ �� ������
 * ���� ������ ���������� �����������
 */
if (count($response['files']) < $mincount) {
	$response['errors'][] = '�� ��������� ����������� ���������� ������ (���������� '.count($response['files']).')';
	if ($debug) {
		$modx->log(modX::LOG_LEVEL_ERROR, '�� ��������� ����������� ���������� ������ (���������� '.count($response['files']).')');
	}
	return $response;
}

$ds = DIRECTORY_SEPARATOR;
/* ����� ���������� */
$targetPath = $_SERVER['DOCUMENT_ROOT'].$ds.$dir.$ds;
$targetPath = str_replace($ds.$ds, $ds, $targetPath);
/**
 * URL ��������, ����������� �����
 */
$dirUrl = str_replace($_SERVER['DOCUMENT_ROOT'], 'http://'.$_SERVER['HTTP_HOST'], $targetPath);
if (!is_dir($targetPath)) {
	mkdir($targetPath, 777, true);
}
if (!is_dir($targetPath)) {
	$response['errors'][] = '�������� ������� ��������';
	if ($debug) {
		$modx->log(modX::LOG_LEVEL_ERROR, '�������� ������� ��������');
	}
	return $response;
}

/**
 * �������� ������� ������
 */
foreach ($response['files'] as $k => &$file) {
	if ($file['error']) {
		$response['errors'][] = '������ ��� �������� ����� '.$file['name'].': '.getFileUploadErrorDescription($file['error']);
		if ($debug) {
			$modx->log(modX::LOG_LEVEL_ERROR, '������ ��� �������� ����� '.$file['name']);
		}
		return $response;
	}
	if ($file['size'] > $size) {
		$response['errors'][] = '���� '.$file['name'].' ������� �������';
		if ($debug) {
			$modx->log(modX::LOG_LEVEL_ERROR, '���� '.$file['name'].' ������� �������');
		}
		return $response;
	}
	/**
	 * ���������� �����
	 */
	$extension = getFileExtension($file['name']);
	/**
	 * ������ ����������� ���������� ������
	 */
	$allowedExtensions = explode(',', $types);
	/**
	 * �������� �� ���� ������� ���� � ��������
	 */
	if (!in_array($extension, $allowedExtensions)) {
		$response['errors'][] = '���� '.$file['name'].' (���������� '.$extension.') �������� � ��������';
		if ($debug) {
			$modx->log(modX::LOG_LEVEL_ERROR, '���� '.$file['name'].' �������� � ��������');
		}
		return $response;
	}
}

/**
 * ����������� ������
 */
$fileUrls = array();
foreach ($response['files'] as $k => &$file) {
	// ��������� ��� �����
	$tempFile = $file['tmp_name'];
	// ������ ����� �����
	$extension = getFileExtension($file['name']);
	$filename = genFilename().'.'.$extension;
	$file['filename'] = $filename;
	$targetFile =  $targetPath.$filename;

	/* ���� �� ������� ����������� ���� */
	if (!move_uploaded_file($tempFile, $targetFile)) {
		$response['errors'][] = '�� ������� ��������� ���� '.$file['name'];
		if (!is_writable($targetPath)) {
			$response['errors'][] = '������� �� �������� ��� ������';
		}
		if ($debug) {
			$modx->log(modX::LOG_LEVEL_ERROR, '�� ������� ��������� ���� '.$file['name']." $tempFile to $targetFile");
		}
		return $response;
	}
	$fileUrls[] = $dirUrl.$filename;
}
$response['placeholders']['files'] = implode(',', $fileUrls);

if (!count($response['errors'])) {
	$response['success'] = true;
	if (count($response['files']) == 1) {
		$response['messages'][] = '���� ������� ��������';
	} elseif (count($response['files']) > 1) {
		$response['messages'][] = '��� ����� ������� ���������';
	}
	if ($debug) {
		$modx->log(modX::LOG_LEVEL_ERROR, '��� ����� ('.count($response['files']).') ������� ���������');
	}
}

return $response;