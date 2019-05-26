<?php
/**
 * Created by PhpStorm.
 * User: d8q8
 * Date: 2016/11/21
 * Time: 14:45
 */
//====== UC公用函数 拷贝uc_client目录中client.php文件中的uc_authcode函数 ===============================================
/**
 * UC加密与解密
 * @param string $string 提供需要加密的字符串
 * @param string $operation 加密方式，ENCODE是加密，DECODE是解密
 * @param string $key 密钥，在整合程序时填写的密钥
 * @param int $expiry
 * @return string
 */
function _uc_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4;

    $key = md5($key ? $key : UC_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}

/**
 * 清理反斜框
 * @param $string
 * @return array|string
 */
function _uc_stripslashes($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = _uc_stripslashes($val);
        }
    } else {
        $string = stripslashes($string);
    }

    return $string;
}
//======uc_client目录中lib中的xml.class.php文件========================================================================
/**
 * xml反序列化
 * @param $xml
 * @param bool $isnormal
 * @return array|string
 */
function xml_unserialize($xml, $isnormal = true) {
    $xml_parser = new XML($isnormal);
    $data       = $xml_parser->parse($xml);
    $xml_parser->destruct();

    return $data;
}

/**
 * xml序列化
 * @param $arr
 * @param bool $htmlon
 * @param bool $isnormal
 * @param int $level
 * @return mixed|string
 */
function xml_serialize($arr, $htmlon = false, $isnormal = false, $level = 1) {
    $s     = $level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<root>\r\n" : '';
    $space = str_repeat("\t", $level);
    foreach ($arr as $k => $v) {
        if (!is_array($v)) {
            $s .= $space . "<item id=\"$k\">" . ($htmlon ? '<![CDATA[' : '') . $v . ($htmlon ? ']]>' : '') . "</item>\r\n";
        } else {
            $s .= $space . "<item id=\"$k\">\r\n" . xml_serialize($v, $htmlon, $isnormal, $level + 1) . $space . "</item>\r\n";
        }
    }
    $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);

    return $level == 1 ? $s . "</root>" : $s;
}

/**
 * xml处理类
 * Class XML
 */
class XML {
    private $dom;

    public function __construct(){
        $this->dom = new DomDocument();
    }

    public function parse($xml){
        $xml = str_replace('encoding="ISO-8859-1"', 'encoding="UTF-8"', $xml);
        $this->dom->loadXML($xml);
        $root = $this->dom->getElementsByTagName('root');
        if($root->length === 1){
            $root = $root->item(0);
            $data = [];
            $this->getData($root, $data);
            return $data;
        } else {
            return false;
        }
    }

    public function getAttr($attr){
        $data = [];
        for($i = 0; $i < $attr->length; $i ++){
            $one = $attr->item($i);
            $data[$one->nodeName] = $one->nodeValue;
        }
        return $data;
    }

    public function getData($node, &$data){
        $attr = $this->getAttr($node->attributes);
        if(isset($attr['id'])){
            $id = $attr['id'];
            $nodeData = [$id, $node->nodeValue];
            $childData = &$data[$id];
        } else {
            $nodeData = false;
            $childData = &$data;
        }
        $child = $node->childNodes;
        $hasChild = false;
        for($i = 0; $i < $child->length; $i ++){
            $one = $child->item($i);
            if($one->nodeType === 1){
                $hasChild = true;
                $this->getData($child->item($i), $childData);
            }
        }
        if(!$hasChild && $nodeData){
            $data[$nodeData[0]] = $nodeData[1];
        }
    }

    public function destruct(){
        
    }
}