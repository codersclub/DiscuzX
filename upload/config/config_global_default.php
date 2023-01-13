<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: config_global_default.php 36362 2017-02-04 02:02:03Z nemohou $
 */

$_config = array();

// 提示：自当前版本起，本文件不支持调用系统内任何变量或函数，请依赖此行为的站点修正实现 //

// ----------------------------  CONFIG DB  ----------------------------- //
// ----------------------------  数据库相关设置---------------------------- //

/**
 * 数据库主服务器设置, 支持多组服务器设置, 当设置多组服务器时, 则会根据分布式策略使用某个服务器
 * @example
 * $_config['db']['1']['dbhost'] = 'localhost'; // 服务器地址
 * $_config['db']['1']['dbuser'] = 'root'; // 用户
 * $_config['db']['1']['dbpw'] = 'root';// 密码
 * $_config['db']['1']['dbcharset'] = 'gbk';// 字符集
 * $_config['db']['1']['pconnect'] = '0';// 是否持续连接
 * $_config['db']['1']['dbname'] = 'x1';// 数据库
 * $_config['db']['1']['tablepre'] = 'pre_';// 表名前缀
 *
 * $_config['db']['2']['dbhost'] = 'localhost';
 * ...
 *
 */
$_config['db'][1]['dbhost']  		= '127.0.0.1';
$_config['db'][1]['dbuser']  		= 'root';
$_config['db'][1]['dbpw'] 	 	= '';
$_config['db'][1]['dbcharset'] 		= 'utf8mb4';
$_config['db'][1]['pconnect'] 		= 0;
$_config['db'][1]['dbname']  		= 'ultrax';
$_config['db'][1]['tablepre'] 		= 'pre_';

/**
 * 数据库从服务器设置( slave, 只读 ), 支持多组服务器设置, 当设置多组服务器时, 系统根据每次随机使用
 * @example
 * $_config['db']['1']['slave']['1']['dbhost'] = 'localhost';
 * $_config['db']['1']['slave']['1']['dbuser'] = 'root';
 * $_config['db']['1']['slave']['1']['dbpw'] = 'root';
 * $_config['db']['1']['slave']['1']['dbcharset'] = 'gbk';
 * $_config['db']['1']['slave']['1']['pconnect'] = '0';
 * $_config['db']['1']['slave']['1']['dbname'] = 'x1';
 * $_config['db']['1']['slave']['1']['tablepre'] = 'pre_';
 * $_config['db']['1']['slave']['1']['weight'] = '0'; //权重：数据越大权重越高
 *
 * $_config['db']['1']['slave']['2']['dbhost'] = 'localhost';
 * ...
 *
 */
$_config['db']['1']['slave'] = array();

//启用从服务器的开关
$_config['db']['slave'] = false;
/**
 * 数据库 分布部署策略设置
 *
 * @example 将 common_member 部署到第二服务器, common_session 部署在第三服务器, 则设置为
 * $_config['db']['map']['common_member'] = 2;
 * $_config['db']['map']['common_session'] = 3;
 *
 * 对于没有明确声明服务器的表, 则一律默认部署在第一服务器上
 *
 */
$_config['db']['map'] = array();

/**
 * 数据库 公共设置, 此类设置通常对针对每个部署的服务器
 */
$_config['db']['common'] = array();

/**
 *  禁用从数据库的数据表, 表名字之间使用逗号分割
 *
 * @example common_session, common_member 这两个表仅从主服务器读写, 不使用从服务器
 * $_config['db']['common']['slave_except_table'] = 'common_session, common_member';
 *
 */
$_config['db']['common']['slave_except_table'] = '';

/*
 * 数据库引擎，根据自己的数据库引擎进行设置，3.5之后默认为innodb，之前为myisam
 * 对于从3.4升级到3.5，并且没有转换数据库引擎的用户，在此设置为myisam
 */
$_config['db']['common']['engine'] = 'innodb';

/**
 * 内存服务器优化设置
 * 以下设置需要PHP扩展组件支持，其中 memcache 优先于其他设置，
 * 当 memcache 无法启用时，会自动开启另外的两种优化模式
 */

//内存变量前缀, 可更改,避免同服务器中的程序引用错乱
$_config['memory']['prefix'] = 'discuz_';

/* Redis设置, 需要PHP扩展组件支持, timeout参数的作用没有查证 */
$_config['memory']['redis']['server'] = '';
$_config['memory']['redis']['port'] = 6379;
$_config['memory']['redis']['pconnect'] = 1;
$_config['memory']['redis']['timeout'] = 0;
$_config['memory']['redis']['requirepass'] = '';
$_config['memory']['redis']['db'] = 0;				//这里可以填写0到15的数字，每个站点使用不同的db
/**
 * 此配置现在已经取消，默认对array使用php serializer进行编码保存，其它数据直接原样保存 
 */
// $_config['memory']['redis']['serializer'] = 1;

$_config['memory']['memcache']['server'] = '';			// memcache 服务器地址
$_config['memory']['memcache']['port'] = 11211;			// memcache 服务器端口
$_config['memory']['memcache']['pconnect'] = 1;			// memcache 是否长久连接
$_config['memory']['memcache']['timeout'] = 1;			// memcache 服务器连接超时

$_config['memory']['memcached']['server'] = '';			// memcached 服务器地址
$_config['memory']['memcached']['port'] = 11211;		// memcached 服务器端口


$_config['memory']['apc'] = 0;							// 启动对 APC 的支持
$_config['memory']['apcu'] = 0;							// 启动对 APCu 的支持
$_config['memory']['xcache'] = 0;						// 启动对 xcache 的支持
$_config['memory']['eaccelerator'] = 0;					// 启动对 eaccelerator 的支持
$_config['memory']['wincache'] = 0;						// 启动对 wincache 的支持
$_config['memory']['yac'] = 0;     						//启动对 YAC 的支持
$_config['memory']['file']['server'] = '';				// File 缓存存放目录，如设置为 data/cache/filecache ，设置后启动 File 缓存
// 服务器相关设置
$_config['server']['id']		= 1;			// 服务器编号，多webserver的时候，用于标识当前服务器的ID

// 附件下载相关
//
// 本地文件读取模式; 模式2为最节省内存方式，但不支持多线程下载
// 如需附件URL地址、媒体附件播放，需选择支持Range参数的读取模式1或4，其他模式会导致部分浏览器下视频播放异常
// 1=fread 2=readfile 3=fpassthru 4=fpassthru+multiple
$_config['download']['readmod'] = 2;

// 是否启用 X-Sendfile 功能（需要服务器支持）0=close 1=nginx 2=lighttpd 3=apache
$_config['download']['xsendfile']['type'] = 0;

// 启用 nginx X-sendfile 时，论坛附件目录的虚拟映射路径，请使用 / 结尾
$_config['download']['xsendfile']['dir'] = '/down/';

// 页面输出设置
$_config['output']['charset'] 			= 'utf-8';	// 页面字符集
$_config['output']['forceheader']		= 1;		// 强制输出页面字符集，用于避免某些环境乱码
$_config['output']['gzip'] 			= 0;		// 是否采用 Gzip 压缩输出
$_config['output']['tplrefresh'] 		= 1;		// 模板自动刷新开关 0=关闭, 1=打开
$_config['output']['language'] 			= 'zh_cn';	// 页面语言 zh_cn/zh_tw
$_config['output']['staticurl'] 		= 'static/';	// 站点静态文件路径，“/”结尾
$_config['output']['ajaxvalidate']		= 0;		// 是否严格验证 Ajax 页面的真实性 0=关闭，1=打开
$_config['output']['upgradeinsecure']		= 0;		// 在HTTPS环境下请求浏览器升级HTTP内链到HTTPS，此选项影响外域资源链接且与自定义CSP冲突 0=关闭(默认)，1=打开
$_config['output']['css4legacyie']		= 1;		// 是否加载兼容低版本IE的css文件 0=关闭，1=打开（默认），关闭可避免现代浏览器加载不必要的数据，但IE6-8的显示效果会受较大影响，IE9受较小影响。

// COOKIE 设置
$_config['cookie']['cookiepre'] 		= 'discuz_'; 	// COOKIE前缀
$_config['cookie']['cookiedomain'] 		= ''; 		// COOKIE作用域
$_config['cookie']['cookiepath'] 		= '/'; 		// COOKIE作用路径

// 站点安全设置
$_config['security']['authkey']			= 'asdfasfas';	// 站点加密密钥
$_config['security']['urlxssdefend']		= true;		// 自身 URL XSS 防御
$_config['security']['attackevasive']		= 0;		// CC 攻击防御 1|2|4|8
$_config['security']['onlyremoteaddr']		= 1;		// 用户IP地址获取方式 0=信任HTTP_CLIENT_IP、HTTP_X_FORWARDED_FOR(默认) 1=只信任 REMOTE_ADDR(推荐)
								// 考虑到防止IP撞库攻击、IP限制策略失效的风险，建议您设置为1。使用CDN的用户可以配置ipgetter选项
								// 安全提示：由于UCenter、UC_Client独立性原因，您需要单独在两个应用内定义常量，从而开启功能

$_config['security']['useipban']			= 1;		// 是否开启允许/禁止IP功能，高负载站点可以将此功能疏解至HTTP Server/CDN/SLB/WAF上，降低服务器压力
$_config['security']['querysafe']['status']	= 1;		// 是否开启SQL安全检测，可自动预防SQL注入攻击
$_config['security']['querysafe']['dfunction']	= array('load_file','hex','substring','if','ord','char');
$_config['security']['querysafe']['daction']	= array('@','intooutfile','intodumpfile','unionselect','(select', 'unionall', 'uniondistinct');
$_config['security']['querysafe']['dnote']	= array('/*','*/','#','--','"');
$_config['security']['querysafe']['dlikehex']	= 1;
$_config['security']['querysafe']['afullnote']	= 0;

$_config['security']['creditsafe']['second'] 	= 0;		// 开启用户积分信息安全，可防止并发刷分，满足 times(次数)/second(秒) 的操作无法提交
$_config['security']['creditsafe']['times'] 	= 10;

$_config['security']['fsockopensafe']['port']	= array(80, 443);	//fsockopen 有效的端口
$_config['security']['fsockopensafe']['ipversion']	= array('ipv6', 'ipv4');	//fsockopen 有效的IP协议
$_config['security']['fsockopensafe']['verifypeer']	= false;	// fsockopen是否验证证书有效性，开启可提升安全性，但需自行解决证书配置问题

$_config['security']['error']['showerror'] = '1';	//是否在数据库或系统严重异常时显示错误详细信息，0=不显示(更安全)，1=显示详细信息(默认)，2=只显示错误本身
$_config['security']['error']['guessplugin'] = '1';	//是否在数据库或系统严重异常时猜测可能报错的插件，0=不猜测，1=猜测(默认)

$_config['admincp']['founder']			= '1';		// 站点创始人：拥有站点管理后台的最高权限，每个站点可以设置 1名或多名创始人
								// 可以使用uid，也可以使用用户名；多个创始人之间请使用逗号“,”分开;
$_config['admincp']['forcesecques']		= 0;		// 管理人员必须设置安全提问才能进入系统设置 0=否, 1=是[安全]
$_config['admincp']['checkip']			= 1;		// 后台管理操作是否验证管理员的 IP, 1=是[安全], 0=否。仅在管理员无法登陆后台时设置 0。
$_config['admincp']['runquery']			= 0;		// 是否允许后台运行 SQL 语句 1=是 0=否[安全]
$_config['admincp']['dbimport']			= 1;		// 是否允许后台恢复论坛数据  1=是 0=否[安全]
$_config['admincp']['mustlogin']		= 1;		// 是否必须前台登录后才允许后台登录  1=是[安全] 0=否

/**
 * 系统远程调用功能模块
 */

// 远程调用: 总开关 0=关  1=开
$_config['remote']['on'] = 0;

// 远程调用: 程序目录名. 出于安全考虑,您可以更改这个目录名, 修改完毕, 请手工修改程序的实际目录
$_config['remote']['dir'] = 'remote';

// 远程调用: 通信密钥. 用于客户端和本服务端的通信加密. 长度不少于 32 位
//          默认值是 $_config['security']['authkey']	的 md5, 您也可以手工指定
$_config['remote']['appkey'] = md5($_config['security']['authkey']);

// 远程调用: 开启外部 cron 任务. 系统内部不再执行cron, cron任务由外部程序激活
$_config['remote']['cron'] = 0;

// $_GET|$_POST的兼容处理，0为关闭，1为开启；开启后即可使用$_G['gp_xx'](xx为变量名，$_GET和$_POST集合的所有变量名)，值为已经addslashes()处理过
// 考虑到安全风险，自X3.5版本起本开关恢复默认值为0的设定，后续版本可能取消此功能，请各位开发人员注意
$_config['input']['compatible'] = 0;

/**
 * IP数据库扩展
 * $_config['ipdb']下除setting外均可用作自定义扩展IP库设置选项，也欢迎大家PR自己的扩展IP库。
 * 扩展IP库的设置，请使用格式：
 * 		$_config['ipdb']['扩展ip库名称']['设置项名称'] = '值';
 * 比如：
 * 		$_config['ipdb']['redis_ip']['server'] = '172.16.1.8';
 */
$_config['ipdb']['setting']['fullstack'] = '';	// 系统使用的全栈IP库，优先级最高
$_config['ipdb']['setting']['default'] = '';	// 系统使用的默认IP库，优先级最低
$_config['ipdb']['setting']['ipv4'] = 'tiny';	// 系统使用的默认IPv4库，留空为使用默认库
$_config['ipdb']['setting']['ipv6'] = 'v6wry'; // 系统使用的默认IPv6库，留空为使用默认库

/**
 * IP获取扩展
 * 考虑到不同的CDN服务供应商提供的判断CDN源IP的策略不同，您可以定义自己服务供应商的IP获取扩展。
 * 为空为使用默认体系，非空情况下会自动调用source/class/ip/getter_值.php内的get方法获取IP地址。
 * 系统提供dnslist(IP反解析域名白名单)、serverlist(IP地址白名单，支持CIDR)、header扩展，具体请参考扩展文件。
 * 性能提示：自带的两款工具由于依赖RDNS、CIDR判定等操作，对系统效率有较大影响，建议大流量站点使用HTTP Server
 * 或CDN/SLB/WAF上的IP黑白名单等逻辑实现CDN IP地址白名单，随后使用header扩展指定服务商提供的IP头的方式实现。
 * 安全提示：由于UCenter、UC_Client独立性及扩展性原因，您需要单独修改相关文件的相关业务逻辑，从而实现此类功能。
 * $_config['ipgetter']下除setting外均可用作自定义IP获取模型设置选项，也欢迎大家PR自己的扩展IP获取模型。
 * 扩展IP获取模型的设置，请使用格式：
 * 		$_config['ipgetter']['IP获取扩展名称']['设置项名称'] = '值';
 * 比如：
 * 		$_config['ipgetter']['onlinechk']['server'] = '100.64.10.24';
 */
$_config['ipgetter']['setting'] = 'header';
$_config['ipgetter']['header']['header'] = 'HTTP_X_FORWARDED_FOR';
$_config['ipgetter']['iplist']['header'] = 'HTTP_X_FORWARDED_FOR';
$_config['ipgetter']['iplist']['list']['0'] = '127.0.0.1';
$_config['ipgetter']['dnslist']['header'] = 'HTTP_X_FORWARDED_FOR';
$_config['ipgetter']['dnslist']['list']['0'] = 'comsenz.com';

// Addon Setting
//$_config['addonsource'] = 'xx1';
//$_config['addon'] = array(
//    'xx1' => array(
//	'website_url' => 'http://127.0.0.1/AppCenter',
//	'download_url' => 'http://127.0.0.1/AppCenter/index.php',
//	'download_ip' => '',
//	'check_url' => 'http://127.0.0.1/AppCenter/?ac=check&file=',
//	'check_ip' => ''
//    )
//);

?>