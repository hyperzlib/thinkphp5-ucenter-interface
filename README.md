# thinkphp5 UCenter 接口
基于 [https://github.com/d8q8/UCenter](https://github.com/d8q8/UCenter) 改造
在原项目基础上完善某些接口并修复bug，同时添加了PHP7的支持
## 用法
把UCenter目录复制到thinkphp的扩展目录（extend），并添加一个控制器，继承UCenter\Controller\ApiController

UCenter后台配置:
```
1.进入UCenter用户中心后台
2.找到左侧菜单[应用管理]
3.点击[添加新应用]按钮
4.进入新应用界面[应用类型]选择[其他]
5.[应用名称]这里可以取个好记的名字
6.[应用的主URL]这里填写[http://域名|IP:PORT/你的控制器]
7.[通信密钥]可以填写自己的密钥，不填写保存后会自动生成
8.[是否开启同步登录/是否接受通知]选择[是]单选按钮
9.保存成功后，返回[应用列表]后[通信情况]是红色的[通信失败]
10.进入编辑，把UCenter后台生成的配置文件拷贝到UCenter模块的Conf目录中的config.php文件中保存即可
11.再次保存后，返回应用列表会变成绿色的[通信成功]字样
12.如果ucenter未接收到应用列表同步，可以进入应用->编辑->保存，即可收到ucenter的同步数据
```

## 感谢
原作者 [https://github.com/d8q8](d8q8)