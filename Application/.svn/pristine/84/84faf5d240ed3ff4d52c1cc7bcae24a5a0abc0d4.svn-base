HI Venus team@2018 .1


#上线步骤
   更新线上机器配置
副仓iwms：
cp -R /home/iwms/app/Application /home/iwms/backup/Application/Application.当前日期
cd /home/iwms/app/Application/
rm -rf *
svn up
cp /home/iwms/conf/config.php  /home/iwms/app/Application/Common/Conf/config.php

软链更新静态页面
ln -s /home/iwms/static/dist/index.html /home/iwms/app/Application/Manage/View/Index/index.html

   更新静态资源
 cd  /home/iwms/static/
 cp -R /home/iwms/static/ /home/iwms/backup/static/static.当前日期
 svn up 
 npm run build

#字典
   主仓：指为了科贸仓库独立部署的仓库系统，是Venus的系统主体
   副仓：指为了项目组运行的功能简化的仓库系统，是Venus的系统精精简版


#说明
#####1.副仓在功能上与主仓的差异
    副仓权限范围只有：库存货品管理，报表数据管理，系统账户管理
    1.1库存货品管理
    1.1.1库存货品管理-创建入仓单，免仓内操作默认是选中，且不可更改
    1.1.2库存货品管理-创建出仓单，添加货品，数量部分，SKU数量不可编辑，SPU数量可编辑
    1.1.3库存货品管理-创建出仓单，免仓内操作默认是选中，且不可更改
    
    1.2报表数据管理
    1.2.1客户单位选项默认选中当前登录账户所属仓库，不可编辑
    
    1.3系统账户管理
    1.3.1系统账户管理-仓库账户管理，添加账户中，权限部分只有，创建入仓单  入仓单管理  创建出仓单  出仓单管理  库存管理  报表管理  仓库账户管理，可编辑。其余隐藏
 
 
#脚本更新
>报表脚本

- 主仓wms：
    - vi /home/wms/app/Application/Common/Script/start_wms_report.php
    - define('APP_DIR', '/home/dev/venus/');前添加//
    - //define('APP_DIR', '/home/wms/app/');去除//
- 副仓iwms：
    - vi /home/iwms/app/Application/Common/Script/start_iwms_report.php
    - define('APP_DIR', '/home/dev/venus/');前添加//
    - //define('APP_DIR', '/home/wms/app/');去除//
>库存检测

- 主仓wms：
    - vi /home/wms/app/Application/Common/Script/check_wms_goods.php
    - define('APP_DIR', '/home/dev/venus/');前添加//
    - define('APP_DIR', '/home/wms/app/');去除//
- 副仓iwms：
    - vi /home/iwms/app/Application/Common/Script/check_iwms_goods.php
    - define('APP_DIR', '/home/dev/venus/');前添加//
    - define('APP_DIR', '/home/wms/app/');去除//
    

