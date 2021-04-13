MX 6.0使用说明

一、平台需求
1.Windows 平台：
IIS/Apache/Nginx + PHP5.4以上版本（注意：PHP5.4dev版本和PHP6均不支持） + MySQL4/5

2.Linux/Unix 平台
Apache + PHP5 + MySQL5

二、程序安装使用
1.将程序目录放在或者上传到网站根目录
2.thinkphp5.0的部署建议是public目录作为web目录访问内容，其它都是web目录之外，
thinkphp5.0默认的应用入口文件位于public/index.php，内容如下：
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

3.建议入口文件为public/index.php  如果不是,修改application下config.php 的$public值 对应样式的路径

三、目录说明
project  应用部署目录
├─application           应用目录（可设置）
│  ├─common             公共模块目录（可更改）
│  ├─console            后台模块(可更改)
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  ├─home               前台目录(可更改)
│  │  ├─config.php      模块配置文件
│  │  ├─common.php      模块函数文件
│  │  ├─controller      控制器目录
│  │  ├─model           模型目录
│  │  ├─view            视图目录
│  │  └─ ...            更多类库目录
│  ├─command.php        命令行工具配置文件
│  ├─common.php         应用公共（函数）文件
│  ├─config.php         应用（公共）配置文件
│  ├─database.php       数据库配置文件
│  ├─tags.php           应用行为扩展定义文件
│  └─route.php          路由配置文件
├─extend                扩展类库目录（可定义）
├─public                WEB 部署目录（对外访问目录）
│  ├─static             静态资源存放目录(css,js,image)
│  ├─index.php          应用入口文件
│  ├─router.php         快速测试文件
│  └─.htaccess          用于 apache 的重写
├─runtime               应用的运行时目录（可写，可设置）
├─vendor                第三方类库目录（Composer）
├─thinkphp              框架系统目录
│  ├─lang               语言包目录
│  ├─library            框架核心类库目录
│  │  ├─think           Think 类库包目录
│  │  └─traits          系统 Traits 目录
│  ├─tpl                系统模板目录
│  ├─.htaccess          用于 apache 的重写
│  ├─.travis.yml        CI 定义文件
│  ├─base.php           基础定义文件
│  ├─composer.json      composer 定义文件
│  ├─console.php        控制台入口文件
│  ├─convention.php     惯例配置文件
│  ├─helper.php         助手函数文件（可选）
│  ├─LICENSE.txt        授权说明文件
│  ├─phpunit.xml        单元测试配置文件
│  ├─README.md          README 文件
│  └─start.php          框架引导文件
├─build.php             自动生成定义文件（参考）
├─composer.json         composer 定义文件
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
├─think                 命令行入口文件

四、功能
1.后台左侧导航和管理员权限
2.上传图片插件和对图片垃圾的处理
3 针对程序员快速完成功能模块的代码生成器
4 列表页使用datatable插件 
5.添加修改页使用bootstrapValidator
6.弹出使用sweetalert插件
7.配置管理使用bootstrap-editable，增加拖拽排序


五、相关资源
thinkphp5 官方手册			下载地址：http://www.kancloud.cn/manual/thinkphp5/118003