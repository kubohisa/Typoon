<?php
/*
	Web Cheep Framework "Typoon".
	Copyright(C)2021 kubohisa.
*/	
	
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	
	require_once '../lib/smarty/Smarty.class.php';
	require_once("../lib/typoon/Typoon.php");

	// Setting.
	class TyRouter {
		public static $SessionName = "hoge";
		public static $domain = "localhost";
		public static $https = false;
		
		public static $rule = array(
				"/form/:hoge/:hogi/:hoe" => "form",
				"/admin" => "adminLogin",
				"/admin/toppage" => "adminTop"
		);
	}
	
	// Echo files.
	$_SERVER = TyRouterInter::sanitizer($_SERVER);
	
	$_url = ltrim($_SERVER["REQUEST_URI"], "\/");
	if ($_url == "" && file_exists("index.html")) {
		TyRouterInter::echo("index.html");
	} elseif (file_exists($_url.".html")) {
		TyRouterInter::echo($_url.".html");
	} elseif(file_exists($_url."/index.html")) {
		TyRouterInter::echo($_url."/index.html");
	}
	
	// ドメインチェック
	if (!preg_match('#\A'.TyRouter::$domain.'\z#i', $_SERVER['SERVER_NAME'])) {
		// 不正アクセス
		TyRouterInter::error(500);
	}
	
	// md Setting.
	mb_language("Japanese");
	mb_internal_encoding("UTF-8");
	
	// Session Seting.
	ini_set('session.cookie_httponly', 1); // http only.
	ini_set('session.use_strict_mode', 1); // server mode only.
	ini_set('session.cookie_secure', 0); // if https then.

	if (TyRouter::$https) {
		if (empty($_SERVER['HTTPS'])) {
			TyRouterInter::error(500);;
		}
		ini_set('session.cookie_secure', 1); // if https then.
	}
	
	// セッションIDリセット
	session_name(TyRouter::$SessionName);
	session_start();
	session_regenerate_id(); // Delete session error.
	
	// nullバイト
	$_GET = null;
	$_POST = TyRouterInter::sanitizer($_POST);
	$_COOKIE = TyRouterInter::sanitizer($_COOKIE);
	$_urlget = array();
	
	TyData::$post = $_POST;
	TyData::$cookie = $_COOKIE;
	TyData::$server = $_SERVER;

	//
	$_url = $_SERVER["REQUEST_URI"];
	
	// ライフチェック
	if($_url == "/life"){
		echo("life."); exit;
	}
	
	// URLルーティング
	TyRouterInter::$exec = "";
	TyData::$url = $_url;
	
	if ($_url == "/") {
		TyData::$get = array();
		TyRouterInter::execSet("index");
	} else {	
		ksort(TyRouter::$rule);
		foreach(TyRouter::$rule as $key => $value) {
			$key = preg_replace("#\:(.+?)(\/|$)#", "(?<$1>.*?)$2", $key);
			
			if (preg_match("#\A{$key}\z#", $_url, $arr)) {
				foreach($arr as $key_ => $value_){
					$arr[$key_] = preg_replace('#\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z#u', '', urldecode($arr[$key_]));
				}
				TyData::$get = $arr;
				//print_r($arr);
				
				TyRouterInter::execSet($value);
				break;	
			}
		}	
	}
	
	//
	if (TyRouterInter::$exec == "" || !file_exists(TyRouterInter::$exec)) {
		TyRouterInter::error(404);
	}
	
	$smarty = new Smarty();
	$smarty->template_dir = TyData::$dir;
	$smarty->compile_dir  = '../lib/smarty/templates_c/';
	$smarty->cache_dir    = '../lib/smarty/cache/';
	$smarty->escape_html  = true;
	
	// php呼び出し
	require_once(TyRouterInter::$exec);
	exit;
	
	// Classes.
	class TyRouterInter {	
		public static $exec = "";
		
		public static function delSpace($str) {
			return preg_replace('/[\p{C}\p{Z}]/u', '', $str);
		}
		
		public static function sanitizer($arr) {
			if (is_array($arr)){
				return array_map('self::sanitizer', $arr);    
			} else {
				return str_replace("\0", "", $arr);
			}
		}
		
		public static function error($no) {
			header('HTTP', true, $no);
			//header("Location: {$no}.html");
			exit;
		}
		
		public static function echo($file) {
			if (!file_exists($file)) {
				TyRouterInter::error(404);
			}
			echo(file_get_contents ($file));
			exit;
		}
		
		public static function execSet($value) {
			TyData::$dir = "../src/action/".$value."/";
			TyRouterInter::$exec = TyData::$dir."index.php";
		}
	}
	
	use TyData as TyD;
	
	class TyData {
		public static $get = array();
		public static $post = array();
		public static $cookie = array();
		public static $server = array();

		public static $param = array();
		public static $error = array();
		
		public static $url = "";
		public static $dir = "";
	}
	