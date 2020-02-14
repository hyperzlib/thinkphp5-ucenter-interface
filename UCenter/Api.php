<?php
namespace UCenter;
/**
 * UC统一客户端API
 * Class Api
 * @package Ucenter
 */
class Api {
    /**
     * 构造函数入口,检测UC配置与官户端文件
     * UcApi constructor.
     */
    public function __construct() {
	require_once(__DIR__ . '/config.php');   
	if (!defined('UC_API')) {
            exception('未发现ucnter配置文件');
        }
        require_once(__DIR__ . '/uc_client/client.php'); // 加载UC客户端主脚本
    }

    /**
     * 获取client.php里所有方法
     * @param $method
     * @param $params
     * @return int|mixed
     */
    public function __call($method, $params) {
        $method = parse_name($method, 0); //函数命名风格转换，兼容驼峰法
        if (function_exists($method)) {
            return call_user_func_array($method, $params);
        } else {
            return -1; //api函数不存在
        }
    }
}
