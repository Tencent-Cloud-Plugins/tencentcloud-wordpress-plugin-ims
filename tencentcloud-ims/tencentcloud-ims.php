<?php
/*
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
Plugin Name: tencentcloud-ims
Plugin URI: https://openapp.qq.com/docs/Wordpress/ims.html
Author URI: https://cloud.tencent.com/product/ims
Description: 通过腾讯云图片内容安全服务对媒体库上传和URL插入的图片进行内容检测和过滤。
Version: 1.0.0
Author: 腾讯云
 */
defined('TENCENT_WORDPRESS_IMS_VERSION')||define( 'TENCENT_WORDPRESS_IMS_VERSION', '1.0.0');
defined('TENCENT_WORDPRESS_IMS_OPTIONS')||define( 'TENCENT_WORDPRESS_IMS_OPTIONS', 'tencent_wordpress_ims_options' );
defined('TENCENT_WORDPRESS_IMS_DIR')||define( 'TENCENT_WORDPRESS_IMS_DIR', plugin_dir_path( __FILE__ ) );
defined('TENCENT_WORDPRESS_IMS_BASENAME')||define( 'TENCENT_WORDPRESS_IMS_BASENAME', plugin_basename(__FILE__));
defined('TENCENT_WORDPRESS_IMS_URL')||define( 'TENCENT_WORDPRESS_IMS_URL', plugins_url( 'tencentcloud-ims' ) );
defined('TENCENT_WORDPRESS_IMS_JS_DIR')||define( 'TENCENT_WORDPRESS_IMS_JS_DIR', TENCENT_WORDPRESS_IMS_URL . DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR );
defined('TENCENT_WORDPRESS_IMS_CSS_DIR')||define( 'TENCENT_WORDPRESS_IMS_CSS_DIR', TENCENT_WORDPRESS_IMS_URL . DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR );
//插件中心常量
defined('TENCENT_WORDPRESS_IMS_NAME')||define( 'TENCENT_WORDPRESS_IMS_NAME', 'tencentcloud-plugin-ims');
defined('TENCENT_WORDPRESS_COMMON_OPTIONS')||define( 'TENCENT_WORDPRESS_COMMON_OPTIONS', 'tencent_wordpress_common_options' );
defined('TENCENT_WORDPRESS_IMS_SHOW_NAME')||define( 'TENCENT_WORDPRESS_IMS_SHOW_NAME', 'tencentcloud-ims');
defined('TENCENT_WORDPRESS_PLUGINS_COMMON_URL')||define('TENCENT_WORDPRESS_PLUGINS_COMMON_URL', TENCENT_WORDPRESS_IMS_URL .DIRECTORY_SEPARATOR. 'common' . DIRECTORY_SEPARATOR);
defined('TENCENT_WORDPRESS_PLUGINS_COMMON_DIR')||define('TENCENT_WORDPRESS_PLUGINS_COMMON_DIR', TENCENT_WORDPRESS_IMS_DIR . 'common' . DIRECTORY_SEPARATOR);
defined('TENCENT_WORDPRESS_PLUGINS_COMMON_CSS_URL')||define('TENCENT_WORDPRESS_PLUGINS_COMMON_CSS_URL', TENCENT_WORDPRESS_PLUGINS_COMMON_URL . 'css' . DIRECTORY_SEPARATOR);

if (!is_file(TENCENT_WORDPRESS_IMS_DIR.'vendor/autoload.php')) {
    wp_die('缺少依赖文件，请确保安装了腾讯云sdk','缺少依赖文件',array('back_link'=>true));
}
require_once 'vendor/autoload.php';
use TencentWordpressIMS\TencentWordPressIMSActions;

$IMSPluginActions = new TencentWordPressIMSActions();

register_activation_hook(__FILE__, array($IMSPluginActions, 'initPlugin'));
register_deactivation_hook(__FILE__,array($IMSPluginActions, 'disablePlugin'));
//卸载
register_uninstall_hook(__FILE__,array(TencentWordPressIMSActions::class, 'uninstallPlugin'));
//插件中心初始化
add_action('init',array($IMSPluginActions, 'initCommonSettingPage'));

//添加插件设置页面
add_action('admin_menu', array($IMSPluginActions, 'pluginSettingPage'));
// 插件列表加入设置按钮
add_filter('plugin_action_links', array($IMSPluginActions, 'pluginSettingPageLinkButton'), 101, 2);
//ajax保存配置
add_action('wp_ajax_update_IMS_options', array($IMSPluginActions, 'updateIMSOptions'));

//只在上传图片的时候
if (strpos($_SERVER['REQUEST_URI'], '/async-upload.php') !== false) {
    add_filter( 'wp_handle_upload_prefilter', array($IMSPluginActions, 'examineImageInMedia') );
}

//在文章发布前
add_filter( 'wp_insert_post_data', array($IMSPluginActions, 'examineImageInPost'), 101, 2 );


//js脚本引入
add_action('admin_enqueue_scripts', array($IMSPluginActions, 'loadMyScriptEnqueue'));
add_action('login_enqueue_scripts', array($IMSPluginActions, 'loadMyScriptEnqueue'));
add_action( 'admin_enqueue_scripts', array($IMSPluginActions, 'loadCSSEnqueue'),100,1);