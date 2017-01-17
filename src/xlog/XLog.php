<?php
/**
 * Created by PhpStorm.
 * User: lio
 * Date: 2017/1/13
 * Time: 下午3:38
 */

namespace xlog;


class XLog extends Log{

    const API_ACCESS 	= "API_ACCESS";
    const API_ERROR 	= "API_ERROR";
    const API_FATAL 	= "API_FATAL";
    const API_EXCEPTION = "API_EXCEPTION";
    const REMOTE_ACCESS = "REMOTE_ACCESS";
    const REMOTE_ERROR 	= "REMOTE_ERROR";
    const DEFAULT_LOG	= 'DEFAULT';	// 默认type 如果不传入,则取值

    //日志级别
    const EMERG     = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT     = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const ERR       = 'ERR';  	// 一般错误: 一般性错误
    const DEBUG     = 'DEBUG';  // 一般错误: 一般性错误
    const INFO		= 'INFO';   // 日志信息log
    const RECORD	= 'RECORD';	// 一般性日志

    static $config = [
        'DEPR' => '^',
    ];

    static $type   = null;

    public static function init($config) {
        if (is_array($config)) {
            self::$config = array_merge(self::$config,$config);
        }
    }

    public static function api($info,$level = self::RECORD) {
        self::log($info,self::API_ACCESS,$level);
    }

    public static function error($info,$level = self::RECORD) {
        self::log($info,self::API_ERROR,$level);
    }

    public static function access($info,$level = self::RECORD){
        self::log($info, self::REMOTE_ACCESS, $level);
    }

    public static function fatal($info,$level = self::EMERG) {
        self::log($info,self::API_FATAL,$level);
    }

    public static function exception($info,$level = self::EMERG) {
        self::log($info,self::API_EXCEPTION,$level);
    }

    public static function access_error($info,$level = self::RECORD) {
        self::log($info,self::REMOTE_ERROR,$level);
    }

    public static function log($info,$type = self::DEFAULT_LOG,$level = self::ERR) {
        if ( false !== strpos(self::$config['LOG_LEVEL'],$level)) {
            self::$type = $type;
            $destination = self::$config['LOG_ROOT'];
            if (isset(self::$config["DESTINATION_FUNC"]) && function_exists(self::$config["DESTINATION_FUNC"])) {
                $destination .= call_user_func(self::$config["DESTINATION_FUNC"],$info,$type);
            }else {
                $destination .= $type."_".date('y_m_d').'.log';
            }
            self::write($info,$destination,$level);
        }
    }

    private static function write($info,$destination='',$level = self::ERR) {
        $info_str = self::to_string($info);
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        error_log(self::$type.self::$config['DEPR'].$level.self::$config['DEPR'].$info_str."\r\n", 3,$destination);
    }

    private static function to_string($info) {
        if (isset(self::$config["TO_STRING_FUNC"]) && function_exists(self::$config["TO_STRING_FUNC"])) {
            return call_user_func(self::$config["TO_STRING_FUNC"],$info,self::$type);
        }
        $log_str = date("Y-m-d H:i:s",time());
        if (is_array($info)) {
            $is_list = self::is_list($info);
            if ($is_list) {
                array_walk($info,function(&$value) {
                    if (!is_string($value))
                        $value = json_encode($value,JSON_UNESCAPED_UNICODE);
                });
                $log_str = "$log_str".self::$config['DEPR'].implode(self::$config['DEPR'],$info);
            }else {
                foreach ($info as $key => $value) {
                    $log_str = "$log_str".self::$config['DEPR'].($is_list?"":$key."=").(is_string($value)?$value:json_encode($value,JSON_UNESCAPED_UNICODE));
                }
            }
            return str_replace(["\r","\n","\t"],[' ',' ',' '],$log_str);
        }
        return $log_str.self::$config['DEPR'].str_replace(["\r","\n","\t"],[' ',' ',' '],$info);
    }

    private static function is_list(array $a) {
        $count = count($a);
        if ($count === 0) return true;
        return !array_diff_key($a, array_fill(0, $count, NULL));
    }
}