<?php
namespace UCenter\Controller;

use think\Controller;
use think\Log;
use think\Loader;
define('UC_CLIENT_PATH', dirname(__DIR__) . '/uc_client');
require_once(dirname(__DIR__) . '/function.php');

/**
 * 所有UCenter的接口,来自uc.php文件
 * Class ApiController
 * @package UCenter\Controller
 */
class ApiController extends Controller
{
    const UC_CLIENT_VERSION = '1.6.0';
    const UC_CLIENT_RELEASE = '20110501';

    const API_DELETEUSER = 1; // note 用户删除 API 接口开关
    const API_RENAMEUSER = 1; // note 用户改名 API 接口开关
    const API_GETTAG = 1; // note 获取标签 API 接口开关
    const API_SYNLOGIN = 1; // note 同步登录 API 接口开关
    const API_SYNLOGOUT = 1; // note 同步登出 API 接口开关
    const API_UPDATEPW = 1; // note 更改用户密码 开关
    const API_UPDATEBADWORDS = 1; // note 更新关键字列表 开关
    const API_UPDATEHOSTS = 1; // note 更新域名解析缓存 开关
    const API_UPDATEAPPS = 1; // note 更新应用列表 开关
    const API_UPDATECLIENT = 1; // note 更新客户端缓存 开关
    const API_UPDATECREDIT = 1; // note 更新用户积分 开关
    const API_GETCREDITSETTINGS = 1; // note 向 UCenter 提供积分设置 开关
    const API_GETCREDIT = 1; // note 获取用户的某项积分 开关
    const API_UPDATECREDITSETTINGS = 1; // note 更新应用积分设置 开关
    const API_ADDFEED = 1; // note FEED推送 开关; UCenter HOME专用，要用这个功能，请在UCENTER后台把应用类型改为UCenter HOME

    const API_RETURN_SUCCEED = '1';
    const API_RETURN_FAILED = '-1';
    const API_RETURN_FORBIDDEN = '-2';

    //继承时请重写此变量到正确的Model类位置
    public $eventListener = 'UCenter\Model\EventModel';
    public $config;

    /**
     * 构造方法，检测相关配置
     */
    public function __construct()
    {
        include(dirname(__DIR__) . '/config.php');
        if (!defined('UC_API')) {
            exit('未发现uc配置文件');
        }
        $this->config = config('ucenter');
        $this->initRequest(); // 初始化请求
    }

    public function uc(){

    }

    protected function emit($event, ...$args){
        $method = $this->eventListener . '::' . 'on' . strtoupper(substr($event, 0, 1)) . substr($event, 1);
        return call_user_func_array($method, $args);
    }

    /**
     * 解析请求
     * @return bool
     */
    protected function initRequest()
    {
        $get = $post = array();
        $code = input('code', '');
        parse_str(_uc_authcode($code, 'DECODE', UC_KEY), $get);

        if (get_magic_quotes_gpc()) {
            $get = _uc_stripslashes($get);
        }

        if (empty($get)) {
            exit('非法请求');
        }
        if (time() - $get['time'] > 3600) {
            exit('请求有效期已过');
        }

        $action = Loader::parseName($get['action'], '1'); // 命名转为JAVA风格

        $xml = file_get_contents('php://input');
        if ($xml) {
            Log::record('XML: ' . $xml);
            $post = xml_unserialize($xml);
        }

        Log::record('[UCenter]');
        Log::record('GET: ' . var_export($get, true));
        Log::record('POST: ' . var_export($post, true));

        if (method_exists($this, $action)) {
            exit(call_user_func([$this, $action], $get, $post));
        } else {
            exit($action . '方法未定义');
        }
    }

    /**
     * 此接口供仅测试连接。当 UCenter 发起 test 的接口请求时，如果成功获取到接口返回的 API_RETURN_SUCCEED 值，表示 UCenter 和应用通讯正常
     * @param $get
     * @param $post
     * @return string
     */
    protected function test($get, $post)
    {
        return self::API_RETURN_SUCCEED;
    }

    /**
     * 当 UCenter 删除一个用户时，会发起 deleteuser 的接口请求，通知所有应用程序删除相应的用户
     * @param $get
     * @param $post
     * @return mixed
     */
    protected function deleteuser($get, $post)
    {
        if (!self::API_DELETEUSER) {
            return self::API_RETURN_FORBIDDEN;
        }
        $uids = explode(',', str_replace("'", '', stripslashes($get['ids'])));
        $this->emit('deleteUser', $uids);
        // TODO:
        return self::API_RETURN_SUCCEED;
    }

    /**
     * 当 UCenter 更改一个用户的用户名时，会发起 renameuser 的接口请求，通知所有应用程序改名
     * @param $get
     * @param $post
     * @return mixed
     */
    protected function renameuser($get, $post)
    {
        if (!self::API_RENAMEUSER) {
            return self::API_RETURN_FORBIDDEN;
        }
        $uid = $get['uid'];
        $oldUserName = $data['oldusername'];
        $newUserName = $data['newusername'];
        $this->emit('renameUser', $uid, $oldUserName, $newUserName);
        // TODO:
        return self::API_RETURN_SUCCEED;
    }

    /**
     * 当用户更改用户密码时，此接口负责接受 UCenter 发来的新密码
     * @param $get
     * @param $post
     * @return string
     */
    protected function updatepw($get, $post)
    {
        if (!self::API_UPDATEPW) {
            return self::API_RETURN_FORBIDDEN;
        }

        $username = $get['username'];
        $this->emit('updatePassword', $username);
        // TODO:
        return self::API_RETURN_SUCCEED;
    }

    /**
     * 如果应用程序存在标签功能，可以通过此接口把应用程序的标签数据传递给 UCenter
     * @param $get
     * @param $post
     * @return mixed
     */
    protected function gettag($get, $post)
    {
        if (!self::API_GETTAG) {
            return self::API_RETURN_FORBIDDEN;
        }

        return $this->_serialize([$get['id'], []], 1);
    }

    protected function _serialize($arr, $htmlon = 0)
    {
        return xml_serialize($arr, $htmlon);
    }

    /**
     * 如果应用程序需要和其他应用程序进行同步登录，此部分代码负责标记指定用户的登录状态
     * @param $get
     * @param $post
     * @return mixed
     */
    protected function synlogin($get, $post)
    {
        //todo 你可以在这里直接写登陆该应用的代码，不必要用下面这种COOKIE方式
        if (!self::API_SYNLOGIN) {
            return self::API_RETURN_FORBIDDEN;
        }

        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');

        $uid = intval($get['uid']);
        $username = $get['username'];
        // 感谢网友(小9)建议,考虑低版本
        $au = $this->config['AuthPre'];
        $authPre = !empty($au) ? $au : 'Example_';
        cookie('auth', _uc_authcode($uid . "\t" . $username, 'ENCODE'), ['prefix' => $authPre]);
    }

    /**
     * 如果应用程序需要和其他应用程序进行同步退出登录，此部分代码负责撤销用户的登录的状态
     * @param $get
     * @param $post
     * @return string
     */
    protected function synlogout($get, $post)
    {
        if (!self::API_SYNLOGOUT) {
            return self::API_RETURN_FORBIDDEN;
        }

        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        // 感谢网友(小9)建议,考虑低版本
        $au = $this->config['AuthPre'];
        $authPre = !empty($au) ? $au : 'Example_';
        cookie('auth', null, ['prefix' => $authPre]);
    }

    /**
     * 当 UCenter 的词语过滤设置变更时，此接口负责通知所有应用程序更新后的词语过滤设置内容
     * @param $get
     * @param $post
     * @return string
     */
    protected function updatebadwords($get, $post)
    {
        if (!self::API_UPDATEBADWORDS) {
            return self::API_RETURN_FORBIDDEN;
        }

        //uc 写 badword 缓存文件
        $cache_file = UC_CLIENT_PATH . '/data/cache/badwords.php';
        $data = array();
        if (is_array($post)) {
            foreach ($post as $k => $v) {
                $data['findpattern'][$k] = $v['findpattern'];
                $data['replace'][$k] = $v['replacement'];
            }
        }
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'badwords\'] = ' . var_export($data, true) . ";\r\n";
        file_put_contents($cache_file, $s);
        return self::API_RETURN_SUCCEED;
    }

    /**
     * 当 UCenter 的域名解析设置变更时，此接口负责通知所有应用程序更新后的域名解析设置内容
     * @param $get
     * @param $post
     * @return string
     */
    protected function updatehosts($get, $post)
    {
        if (!self::API_UPDATEHOSTS) {
            return self::API_RETURN_FORBIDDEN;
        }
//        @unlink(UC_CLIENT_PATH . '/data/cache/hosts.php');

        //uc 写 host 缓存文件
        $cache_file = UC_CLIENT_PATH . '/data/cache/hosts.php';
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'hosts\'] = ' . var_export($post, TRUE) . ";\r\n";
        file_put_contents($cache_file, $s);

        return self::API_RETURN_SUCCEED;
    }

    /**
     * 当 UCenter 的应用程序列表变更时，此接口负责通知所有应用程序更新后的应用程序列表
     * @param $get
     * @param $post
     * @return string
     */
    protected function updateapps($get, $post)
    {
        if (!self::API_UPDATEAPPS) {
            return self::API_RETURN_FORBIDDEN;
        }
//        @unlink(UC_CLIENT_PATH . '/data/cache/apps.php');

        //uc 写 app 缓存文件
        $cache_file = UC_CLIENT_PATH . '/data/cache/apps.php';
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'apps\'] = ' . var_export($post, TRUE) . ";\r\n";
        file_put_contents($cache_file, $s);

        return self::API_RETURN_SUCCEED;
    }

    /**
     * 当 UCenter 的基本设置信息变更时，此接口负责通知所有应用程序更新后的基本设置内容
     * @param $get
     * @param $post
     * @return string
     */
    protected function updateclient($get, $post)
    {
        if (!self::API_UPDATECLIENT) {
            return self::API_RETURN_FORBIDDEN;
        }
//        @unlink(UC_CLIENT_PATH . '/data/cache/settings.php');

        //uc 写 settings 缓存文件
        $cache_file = UC_CLIENT_PATH . '/data/cache/settings.php';
        $s = "<?php\r\n";
        $s .= '$_CACHE[\'settings\'] = ' . var_export($post, TRUE) . ";\r\n";
        file_put_contents($cache_file, $s);
        
        return self::API_RETURN_SUCCEED;
    }

    /**
     *当某应用执行了积分兑换请求的接口函数 uc_credit_exchange_request() 后，此接口负责通知被兑换的目的应用程序所需修改的用户积分值。
     * @param $get
     * @param $post
     * @return string
     */
    protected function updatecredit($get, $post)
    {
        if (!self::API_UPDATECREDIT) {
            return self::API_RETURN_FORBIDDEN;
        }
        $credit = $get['credit'];
        $amount = $get['amount'];
        $uid = $get['uid'];
        $this->emit('updateCredit', $uid, $credit, $amount);
        return self::API_RETURN_SUCCEED;
    }

    /**
     * 此接口负责把应用程序的积分设置传递给 UCenter，以供 UCenter 在积分兑换设置中使用
     * @param $get
     * @param $post
     * @return mixed|string
     */
    protected function getcreditsettings($get, $post)
    {
        if (!self::API_GETCREDITSETTINGS) {
            return self::API_RETURN_FORBIDDEN;
        }

        $credits = $this->emit('getCreditSettings');

        // TODO:
        return $this->_serialize($credits);
    }

    /**
     * 此接口负责接收 UCenter 积分兑换设置的参数
     * @param $get
     * @param $post
     * @return string
     */
    protected function updatecreditsettings($get, $post)
    {
        if (!self::API_UPDATECREDITSETTINGS) {
            return self::API_RETURN_FORBIDDEN;
        }

        // TODO:
        return self::API_RETURN_SUCCEED;
    }

    /**
     *此接口用于把应用程序中指定用户的积分传递给 UCenter
     * @param $get
     * @param $post
     * @return string
     */
    protected function getcredit($get, $post)
    {
        if (!self::API_GETCREDIT) {
            return self::API_RETURN_FORBIDDEN;
        }

        return self::API_RETURN_SUCCEED;
    }

    /**
     * 此接口负责推送动态到所有应用程序里，UCHOME HOME专用
     * @param $get
     * @param $post
     * @return string
     */
    protected function addfeed($get, $post)
    {
        if (!self::API_ADDFEED) {
            return self::API_RETURN_FORBIDDEN;
        }

        //$this->emit('addFeed');
        return self::API_RETURN_SUCCEED;
    }
}