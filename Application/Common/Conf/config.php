<?php

return array(
    /* 系统版本号 */
    "VERSION" => "Venus.mini.0.0.1",
    /* 账户秘钥 */
    "AUTH_SECRET_KEY" => "20180101",
    /* 主机域名 */
    "WMS_HOST" => $_SERVER['HTTP_HOST'],

    /* 数据库设置 */
    'DB_TYPE' => 'mysql',         // 数据库类型
    'DB_HOST' => '127.0.0.1',     //'127.0.0.1',  // 服务器地址180.76.106.103
    'DB_NAME' => 'venus_iwms',     // 数据库名
//    'DB_USER' => 'venusdb',          // 用 户名
    'DB_USER' => 'root',          // 用 户名
//    'DB_PWD' => 'RVbHZAm88f5ufos9',  // 密码
    'DB_PWD' => '931101Li',  // 密码
    'DB_PORT' => '3306',          // 端口
    'DB_PREFIX' => 'wms_',        // 数据库表前缀
    'DB_PARAMS' => array(),       // 数据库连接参数
    'DB_DEBUG' => true,           // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE' => true,    // 启用字段缓存
    'DB_CHARSET' => 'utf8',       // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE' => 0,        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE' => false,    // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM' => 1,         // 读写分离后 主服务器数量
    'DB_SLAVE_NO' => '',          // 指定从服务器序号

    //发送email
    'MAIL_SMTP' => TRUE,
    'MAIL_HOST' => 'smtp.ym.163.com',//smtp服务器的名称
    'MAIL_SMTPAUTH' => TRUE, //启用smtp认证
    'MAIL_USERNAME' => 'notice@shijijiaming.com',//发件人的邮箱名
    'MAIL_PASSWORD' => '88888888',//邮箱发件人授权密码
    'MAIL_FROM' => 'notice@shijijiaming.com',//发件人邮箱地址
    'MAIL_FROMNAME' => 'notice',//发件人姓名
    'MAIL_CHARSET' => 'utf-8',//设置邮件编码
    'MAIL_ISHTML' => TRUE, // 是否HTML格式邮件
    'MAIL_SECURE' => 'ssl',

    "FILE_SAVE_PATH" => "/home/dev/venus-mini/Public/files/",
    "FILE_TYPE_NAME" => array(
        "SPU_ALL" => "001",//全部spu数据
        "ORDER_PURCHASE_FILE" => "002",//订单采购单
        "ORDER_GOODSLIST_FILE" => "003",//订单货品清单
        "RECEIPT_GOODSLIST_FILE" => "004",//入仓单货品清单
        "INVOICE_GOODSLIST_FILE" => "005",//出仓单货品清单
        "GOODS_GOODSLIST_FILE" => "006",//库存清单
        "REPORT_RECEIPT" => "010",//入库单
        "REPORT_RECEIPT_COLLECTION" => "011",//入库单汇总
        "REPORT_INVOICE" => "020",//出仓单
        "REPORT_INVOICE_COLLECTION" => "021",//出仓单汇总
        "REPORT_GOODSTORED" => "030",//库存表
        "REPORT_ACCOUNT_DETAIL" => "040",//明细账
        "TPLS" => "tpls"//模板
    ),

    "FILE_TPLS" => "/home/dev/venus/Public/tpls/",//报表模板
    "FILE_ZIP_SAVE_PATH" => "/home/dev/venus/Public/files/zip/",//报表模板

    'SKU_VERSION_KEY' => "SKUVER",//SKU版本前缀
    'SKU_IMG_VERSION' => "v0.0.1",//SKU图片版本号
    'WMS_REMOTE_SERVICE' => 'https://idev.shijijiaming.cn/index.php/wms/remote',

    //存储sku字典文件的文件路径
    'SKU_FILE_PATH' => '/home/dev/venus-mini/Public/files/sku/',

    /* 小程序配置 */
    "WEIXIN_AUTH" => array(
        "AppID" => "wx239e74a75f56876d",               // "wxec9e72e776e44d23",//
        "AppSecret" => "658a586f7ce92e22da1da6725b5d1d3a", //"3dfeb4f20158697a5a96837898ab590a",//
    ),

    /* 加载外部配置 */
    'LOAD_EXT_CONFIG' => 'catalog,dicts',


);