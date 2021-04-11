<?php
class TyDatabaseInter {
	public static $domain = "";
	public static $user = "";
	public static $password = "";
	public static $database = "sqlite.db";
}

class TyForm {
	public static function init (){
		TyData::$error = array();
		TyData::$param = array();
	}

	public static function validate ($name, $data, $list){
		$data_ = mb_convert_encoding($data, "UTF-8", "auto");
		$list_ = explode("|",$list);
		
		foreach($list_ as $value) {
			if ($value == "") continue;
			
			$rule = explode("=",$value);
			$ruleKey = $rule[0];
			if (isset($rule[1])) {
				$ruleValue = $rule[1];
			} else {
				$ruleValue = 0; // dummy.
			}
			
			switch ($ruleKey) {
				case "trim":
					$data_ = preg_replace('#\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z#u', '', $data_);
					break;
				case "plain":
					$data_ = preg_replace('#[\p{C}\p{Z}]#u', '', $data_);
					break;
				case "longtrim":
					$data_ = preg_replace('/\n\r/u', '\r\n', $data_);
					$data_ = preg_replace('/\r\n/u', '\n', $data_);
					$data_ = preg_replace('/\r/u', '\n', $data_);
					$data_ = preg_replace('#\n#u', '\n\xE3\x80\x80', $data_);
					$data_ = "　".preg_replace('#\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z#u', '', $data_); //[\xE3\x80\x80]
					break;

				case "required":
				case "in":
					if (empty($data_)) error($name, $ruleKey);
					break;
				case "notnull":
					if ($data_==null) self::error($name, $ruleKey);
					break;
					
				case "len":
					if (mb_strlen($data_)!=$ruleValue) self::error($name, $ruleKey);
					break;
				case "lenmax":
					if (mb_strlen($data_)>$ruleValue) self::error($name, $ruleKey);
					break;
				case "lenmin":
					if (mb_strlen($data_)<$ruleValue) self::error($name, $ruleKey);
					break;
					
				case "max":
					if (!preg_match("#\A[0-9]+\z#", $data_)) {
						self::error($name, "digit");
					} elseif ($data_>$ruleValue) {
						self::error($name, $ruleKey);
					}
					break;
				case "min":
					if (!preg_match("#\A[0-9]+\z#", $data_)) {
						self::error($name, "digit");
					} elseif ($data_<$ruleValue) {
						self::error($name, $ruleKey);
					}
					break;
				
				// is rule.
				case "regix":
					if (!filter_var($data_, FILTER_VALIDATE_REGEXP)) TyRouterInter::error(500);
					if (!preg_match("#\A{$ruleValue}\z#", $data_)) self::error($name, $ruleKey);
					break;
				case "mail":
					if (!filter_var($data_, FILTER_FLAG_EMAIL_UNICODE)) self::error($name, $ruleKey);
					break;
				case "url":
					if (!preg_match('#\Ahttps?+://#i', $data_)
						&& !filter_var($data_, FILTER_VALIDATE_URL)) self::error($name, $ruleKey);
					break;
				case "alphanumber":
					if (!preg_match("#\A[a-zA-Z0-9]+\z#", $data_)) self::error($name, $ruleKey);
					break;
				case "alpha":
					if (!preg_match("#\A[a-zA-Z]+\z#", $data_)) self::error($name, $ruleKey);
					break;
				case "digit":
					if (!preg_match("#\A[0-9]+\z#", $data_)) self::error($name, $ruleKey);
					break;
//				case "":
//					if (!preg_match("##", $data_)) self::error($name, $ruleKey);
//					break;
			}
		}
		
		TyData::$param[$name] = $data_;
		return $data_;
	}
	
	static function error($name, $key){
		TyData::$error[$name][$key] = true;
	}
	
	static function errorCheck(){
		return empty(TyData::$error);
	}
	
	// Form token.
	public static function token() {
		$_SESSION['TyFormToken'] = hash('ripemd160', microtime());
		return $_SESSION['TyFormToken'];
	}
	
	public static function tokenCheck($str) {
		$check = $_SESSION['TyFormToken'];
		$_SESSION['TyFormToken'] = null;
		
		if ($check == $str) {
			return true;
		}
		return false;
	}
}

class TyText {
	public static function echo($file) {
		if (!file_exists($file)) {
			TyRouterInter::error(404);
		}
		
		echo(file_get_contents($file));
		exit;
	}
	
	public static function toBr($str) {
		$str = preg_replace('/\n\r/u', '\r\n', $str);
		$str = preg_replace('/\r\n/u', '\n', $str);
		$str = preg_replace('/\r/u', '\n', $str);
		$str = preg_replace('/\n/u', '<br>', $str);
		return $str;
	}
	
	public static function toRn($str) {
		$str = preg_replace('/<br>/u', '\r\n', $str);
		return $str;
	}

	public static function randomText($len) {
		$char = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
		
		$text = "";
		for ($i = 0; $i < $len; $i++) {
			$text .= $char[mt_rand(0, count($char) - 1)];
		}
		
		return $text;
	}
	
	public static function timeSectime($y, $mon, $d, $a, $m) {
		return mktime($a, $m, 0, $mon, $d, $y);
	}
	
	public static function timeDateRss($sectime) {
		return date(DATE_RFC822, $sectime);
	}
	
	public static function timeDate($sectime) {
		return getdate($sectime);
	}	
}

class TyLogin {
	public static function loginSave($userId) {
		$_SESSION['TyId'] = $userId;
		self::uidMake($userId);
	}
	
	public static function loginCheck() {
		if (empty($_SESSION['TyUid'])) {
			self::logout();
			exit;
		}
		return true;
	}

	public static function logout() {
		$_SESSION = array();

		setcookie(TyRouter::$SessionName, '', time()-1000);
		setcookie(TyRouter::$SessionName, '', time()-1000, '/');		
		session_destroy();
		
		header("Location: /");
		exit;
	}
	
	static function uidMake($userId) {
		$_SESSION['TyUid'] = hash_hmac("ripemd160", $userId.microtime(), 'secret');
		
		return $_SESSION['TyUid'];
	}
	
	public static function uidCheck($str) {
		if ($_SESSION['TyUid'] == $str) {
			return true;
		}
		return false;
	}
	
	public static function uidGet() {
		return $_SESSION['TyUid'];
	}
}

class TySqlite {
	public static $d;
	
	// mysql
	public static function connect() {
		if (isset(self::$d)) return self::$d;
		
		try {
			self::$d = new PDO('sqlite:../data/'.TyDatabaseInter::$database);
			self::$d->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			self::$d->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return self::$d;
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
	
	public static function get() {
		return self::$d;
	}
	
	public static function exec($sql) {
		try {
			$st = self::$d->exec($sql);
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
	
	public static function execParam($sql, $array) {
		try {
			$st = self::$d->prepare($sql);
			$st->execute($array);
			return;
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
	
	public static function execFetch($sql) {
		try {
			$st = self::$d->prepare($sql);
			$st->execute();
			return $st->fetch();
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
	
	public static function execFetchAll($sql) {
		try {
			$st = self::$d->prepare($sql);
			$st->execute();
			return $st->fetchAll();
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
	
	public static function fetch($sql, $array) {
		try {
			$st = self::$d->prepare($sql);
			$st->execute($array);
			return $st->fetch();
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
	
	public static function fetchAll($sql, $array) {
		try {
			$st = self::$d->prepare($sql);
			$st->execute($array);
			return $st->fetchAll();
		} catch (Exception $e) {
			echo mb_convert_encoding($e->getMessage().PHP_EOL, 'UTF-8', 'auto');
			die;
		}
	}
		
	public static function close() {
		self::$d = null;
	}
}

class TyMail {
	function send($toAdrs, $fromAdrs, $subject, $body) {
		if (!filter_var($toAdrs, FILTER_FLAG_EMAIL_UNICODE)) {
			return false;
		} elseif (!filter_var($fromAdrs, FILTER_FLAG_EMAIL_UNICODE)) {
			return false;
		}
		
		$header = "From: $fromAdrs\nReply-To: $fromAdrs\n";
		return mb_send_mail($toAdrs, $subject, $body, $header);
	}
}

class TyFile {
	static $dir = "";
	
	public static function setDir($dir) {
		self::$dir = rtrim($dir)."/";
	}
	
	public static function filename($param) {
		return $_FILES[$param]['name'];
	}
	
	public static function save($param) {
		if(is_uploaded_file($_FILES[$param]['tmp_name'])){
			if(move_uploaded_file($_FILES[$param]['tmp_name'], self::$dir.$_FILES[$param]['name'])){
				return $_FILES[$param]['name'];
			}
		}
		return "";
	}
	
	public static function delete($param) {
		if(file_exists(self::$dir.$_FILES[$param]['name'])) {
			if(unlink(self::$dir.$_FILES[$param]['name'])){
				return true;
			}
		}
		return false;
	}
	
	public static function size($param) {
		return $_FILES[$param]['size'];
	}
	
	public static function checkImage($param) {
		if (!file_exists(self::$dir.$_FILES[$param]['name'])) return false;
		if (!($type = exif_imagetype(self::$dir.$_FILES[$param]['name']))) return false;
		
		switch ($type) {
			case IMAGETYPE_GIF:
			case IMAGETYPE_JPEG:
			case IMAGETYPE_PNG:
				return true;
				break;
			default:
				return false;
				break;
		}
	}
	
	public static function podcastImage($param) {
		if (!file_exists(self::$dir.$_FILES[$param]['name'])) return false;
		$size = getimagesize(self::$dir.$_FILES[$param]['name']);
		
		$im = null;
		switch ($size[2]) {
			case IMAGETYPE_GIF:
				$im = @imagecreatefromgif(self::$dir.$_FILES[$param]['name']);
				break;
			case IMAGETYPE_JPEG:
				$im = @imagecreatefromjpeg(self::$dir.$_FILES[$param]['name']);
				break;
			case IMAGETYPE_PNG:
				$im = @imagecreatefrompng(self::$dir.$_FILES[$param]['name']);
				break;
			default:
				return false;
				break;
		}
		
		if(!$im) return false;
		
		$dst_im = imagecreate(300, 300);
		
		imagecopyresampled(
			$dst_im,
			$im,
			0,
			0,
			0,
			0,
			300,
			300,
			$size[0],
			$size[1]
		);
		imagepng($dst_im, self::$dir."podcast.png");
		self::delete($param);
	}
	
	public static function guid($param) {
		return hash('sha256', $param);
	}
}
