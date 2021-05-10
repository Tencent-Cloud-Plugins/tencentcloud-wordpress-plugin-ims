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

namespace TencentWordpressIMS;

use Exception;
use TencentCloud\Cms\V20190321\Models\ImageModerationResponse;
use WP_Error;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Cms\V20190321\CmsClient;
use TencentCloud\Cms\V20190321\Models\ImageModerationRequest;
use TencentWordpressPluginsSettingActions;

class TencentWordPressIMSActions
{
    const PLUGIN_TYPE = 'ims';
    const IMAGE_RECORD_TABLE_NAME = 'tencent_wordpress_ims_image_records';

    /**
     * 插件初始化
     */
    public static function initPlugin()
    {
        static::addToPluginCenter();
        static::createIMSImageRecordTable();
        // 第一次开启插件则生成一个全站唯一的站点id，保存在公共的option中
        self::requirePluginCenterClass();
        TencentWordpressPluginsSettingActions::setWordPressSiteID();
        $staticData = self::getTencentCloudWordPressStaticData('activate');
        TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
    }

    /**
     * 禁用插件
     */
    public static function disablePlugin()
    {
        self::requirePluginCenterClass();
        TencentWordpressPluginsSettingActions::disableTencentWordpressPlugin(TENCENT_WORDPRESS_IMS_SHOW_NAME);
        $staticData = self::getTencentCloudWordPressStaticData('deactivate');
        TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
    }

    /**
     * 引入插件中心类
     */
    public static function requirePluginCenterClass()
    {
        require_once TENCENT_WORDPRESS_PLUGINS_COMMON_DIR . 'TencentWordpressPluginsSettingActions.php';
    }

    /**
     * 加入插件中心
     */
    public static function addToPluginCenter()
    {
        self::requirePluginCenterClass();
        $plugin = array(
            'plugin_name' => TENCENT_WORDPRESS_IMS_SHOW_NAME,
            'plugin_dir' => TENCENT_WORDPRESS_IMS_BASENAME,
            'nick_name' => '腾讯云图片内容安全（IMS）插件',
            'href' => "admin.php?page=TencentWordpressIMSSettingPage",
            'activation' => TencentWordpressPluginsSettingActions::ACTIVATION_INSTALL,
            'status' => TencentWordpressPluginsSettingActions::STATUS_OPEN,
            'download_url' => ''
        );
        TencentWordpressPluginsSettingActions::prepareTencentWordressPluginsDB($plugin);
    }

    /**
     * 创建敏感词命中记录表
     * @return bool
     */
    public static function createIMSImageRecordTable()
    {
        $wpdb = $GLOBALS['wpdb'];
        $tableName = $wpdb->prefix . self::IMAGE_RECORD_TABLE_NAME;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") !== $tableName) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
			    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		    	`user_login` varchar(100) NOT NULL DEFAULT '',
			    `user_nicename` varchar(100) NOT NULL DEFAULT '',
			    `user_email` varchar(32) NOT NULL DEFAULT '',
			    `user_role` varchar(32) NOT NULL DEFAULT '',
			    `status` varchar(50) NOT NULL DEFAULT '',
			    `type` varchar(50) NOT NULL DEFAULT '',
			    `file_name` varchar(200) NOT NULL DEFAULT '',
			    `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			    PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        return true;
    }

    /**
     * 初始化插件中心设置页面
     */
    public function initCommonSettingPage()
    {
        self::requirePluginCenterClass();
        if (class_exists('TencentWordpressPluginsSettingActions')) {
            TencentWordpressPluginsSettingActions::init();
        }
    }

    /**
     * 卸载插件
     */
    public static function uninstallPlugin()
    {
        self::requirePluginCenterClass();
        self::deleteIMSImageRecordTable();
        delete_option(TENCENT_WORDPRESS_IMS_OPTIONS);
        TencentWordpressPluginsSettingActions::deleteTencentWordpressPlugin(TENCENT_WORDPRESS_IMS_SHOW_NAME);
        $staticData = self::getTencentCloudWordPressStaticData('uninstall');
        TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
    }


    public static function getTencentCloudWordPressStaticData($action)
    {
        self::requirePluginCenterClass();
        $staticData['action'] = $action;
        $staticData['plugin_type'] = self::PLUGIN_TYPE;
        $staticData['data']['site_id'] = TencentWordpressPluginsSettingActions::getWordPressSiteID();
        $staticData['data']['site_url'] = TencentWordpressPluginsSettingActions::getWordPressSiteUrl();
        $staticData['data']['site_app'] = TencentWordpressPluginsSettingActions::getWordPressSiteApp();
        $IMSOptions = self::getIMSOptionsObject();
        $staticData['data']['uin'] = TencentWordpressPluginsSettingActions::getUserUinBySecret($IMSOptions->getSecretID(), $IMSOptions->getSecretKey());
        $staticData['data']['cust_sec_on'] = $IMSOptions->getCustomKey() === $IMSOptions::CUSTOM_KEY ? 1 : 2;
        return $staticData;
    }

    /**
     * 参数过滤
     * @param $key
     * @param string $default
     * @return string|void
     */
    public function filterPostParam($key, $default = '')
    {
        return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default;
    }

    /**
     * 获取配置对象
     * @return TencentWordpressIMSOptions
     */
    public static function getIMSOptionsObject()
    {
        $IMSOptions = get_option(TENCENT_WORDPRESS_IMS_OPTIONS);
        if ($IMSOptions instanceof TencentWordpressIMSOptions) {
            return $IMSOptions;
        }
        return new TencentWordpressIMSOptions();
    }


    /**
     * 检测在媒体库上传的图片
     * @param $file
     * @return bool
     * @throws Exception
     */
    public function examineImageInMedia($file)
    {
        $IMSOptions = self::getIMSOptionsObject();
        $imgContent = file_get_contents($file['tmp_name']);
        $response = $this->imageModeration($IMSOptions, $imgContent);
        //检测接口异常不进行报错
        if (!($response instanceof ImageModerationResponse)) {
            return $file;
        }
        if ($response->getData()->EvilFlag === 0 || $response->getData()->EvilType === 100) {
            return $file;
        }

        // 命中记录入库
        $user = wp_get_current_user();
        $user_login = $user->data->user_login;
        $user_nicename = $user->data->user_nicename;
        $user_email = $user->data->user_email;
        $user_role = $user->roles[0];
        $status = '上传失败';
        $type = 'picture';
        $file_name = $file['name'];
        $create_time = date('Y-m-d H:i:s', time());
        $this->insertIMSImageRecord($user_login, $user_nicename, $user_email,
            $user_role, $status, $type, $file_name, $create_time);
        $file['error'] = '图片检测不通过，请更换';
        return $file;
    }


    /**
     * 检测在发文章时用第三方URL上传的图片
     * @param $data
     * @param $postarr
     * @return bool
     * @throws Exception
     */
    public function examineImageInPost($data, $postarr)
    {
        //revision进来的请求不进行检测
        if ($postarr['post_type'] !== 'post' || $postarr['ID'] === 0) {
            return $data;
        }
        $IMSOptions = self::getIMSOptionsObject();
        if ($IMSOptions->getCheckUrlImg() !== $IMSOptions::CHECK_URL_IMG) {
            return $data;
        }
        $pattern = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
        $images = [];
        preg_match_all($pattern, $data['post_content'], $images);
        $images = $images[1];
        if (empty($images)) {
            return $data;
        }

        $user = wp_get_current_user();
        $user_login = $user->data->user_login;
        $user_nicename = $user->data->user_nicename;
        $user_email = $user->data->user_email;
        $user_role = $user->roles[0];
        $status = '发布失败';
        $type = 'url';
        $create_time = date('Y-m-d H:i:s', time());
        foreach ($images as $index => $img) {
            $response = $this->imageModeration($IMSOptions, '', trim($img, '\"'));
            //检测接口异常不进行报错
            if (!($response instanceof ImageModerationResponse)) {
                break;
            }
            if ($response->getData()->EvilFlag !== 0 || $response->getData()->EvilType !== 100) {
                // 命中记录入库
                $file_name = $img;
                $this->insertIMSImageRecord($user_login, $user_nicename, $user_email,
                    $user_role, $status, $type, $file_name, $create_time);
                $num = $index + 1;
                $error = new WP_Error(
                    'img_url_examined_fail',
                    __('文章内容包含的第' . $num . '张图片检测不通过，请删除后再提交')
                );
                wp_die($error, '文章包含的图片检测不通过.', ['back_link' => true]);
            }
        }
        return $data;
    }

    /**
     * @param $user_login
     * @param $user_nicename
     * @param $user_email
     * @param $user_role
     * @param $type
     * @param $content
     * @param $post_title
     * @param $evil_label
     * @param $create_time
     * @return mixed
     */
    private function insertIMSImageRecord($user_login, $user_nicename, $user_email, $user_role, $status, $type,
                                          $file_name, $create_time)
    {
        $wpdb = $GLOBALS['wpdb'];
        $tableName = $wpdb->prefix . self::IMAGE_RECORD_TABLE_NAME;
        $sql = "INSERT INTO `{$tableName}` (`user_login`, `user_nicename`, `user_email`, `user_role`, `status`, `type`, `file_name`, `create_time`) 
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s);";
        return $wpdb->query($wpdb->prepare(
            $sql, $user_login, $user_nicename, $user_email, $user_role, $status, $type, $file_name, $create_time
        ));
    }

    /**
     * 卸载插件，删除检查记录
     */
    public static function deleteIMSImageRecordTable()
    {
        $wpdb = $GLOBALS['wpdb'];
        $tableName = $wpdb->prefix . self::IMAGE_RECORD_TABLE_NAME;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") === $tableName) {
            $sql = "DROP TABLE {$tableName};";
            $wpdb->query($sql);
        }
    }


    /**
     * 腾讯云图片检测
     * @param $IMSOptions
     * @param string $imgContent
     * @param string $imgUrl
     * @return Exception|ImageModerationResponse|TencentCloudSDKException
     * @throws Exception
     */
    private function imageModeration($IMSOptions, $imgContent = '', $imgUrl = '')
    {
        try {
            if (empty($imgContent) && empty($imgUrl)) {
                throw new \Exception('图片内容和图片链接不能同时为空');
            }
            $cred = new Credential($IMSOptions->getSecretID(), $IMSOptions->getSecretKey());
            $clientProfile = new ClientProfile();
            $client = new CmsClient($cred, "ap-shanghai", $clientProfile);
            $req = new ImageModerationRequest();
            if ($imgUrl) {
                $params['FileUrl'] = $imgUrl;
            } else {
                $params['FileContent'] = base64_encode($imgContent);
            }
            $req->fromJsonString(\GuzzleHttp\json_encode($params, JSON_UNESCAPED_UNICODE));
            $resp = $client->ImageModeration($req);
            return $resp;
        } catch (TencentCloudSDKException $e) {
            return $e;
        }
    }


    /**
     * 加载js脚本
     */
    public function loadMyScriptEnqueue()
    {
        wp_register_script('IMS_front_user_script', TENCENT_WORDPRESS_IMS_JS_DIR . 'front_user_script.js', array('jquery'), '2.1', true);
        wp_enqueue_script('IMS_front_user_script');

        wp_register_script('IMS_back_admin_script', TENCENT_WORDPRESS_IMS_JS_DIR . 'back_admin_script.js', array('jquery'), '2.1', true);
        wp_enqueue_script('IMS_back_admin_script');

    }

    /**
     * 加载css
     * @param $hookSuffix
     */
    public function loadCSSEnqueue($hookSuffix)
    {
        //只在后台配置页引入
        if (strpos($hookSuffix, 'page_TencentWordpressIMSSettingPage') !== false) {
            wp_register_style('IMS_back_admin_css', TENCENT_WORDPRESS_IMS_CSS_DIR . 'bootstrap.min.css');
            wp_enqueue_style('IMS_back_admin_css');
        }
    }

    /**
     * 添加插件设置页面
     */
    public function pluginSettingPage()
    {
        require_once 'TencentWordpressIMSSettingPage.php';
        TencentWordpressPluginsSettingActions::addTencentWordpressCommonSettingPage();
        add_submenu_page('TencentWordpressPluginsCommonSettingPage', '图片内容安全', '图片内容安全', 'manage_options', 'TencentWordpressIMSSettingPage', 'TencentWordpressIMSSettingPage');
    }


    /**
     * 保存插件配置
     */
    public function updateIMSOptions()
    {
        try {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('msg' => '当前用户无权限'));
            }
            $IMSOptions = new TencentWordpressIMSOptions();
            $IMSOptions->setCustomKey($this->filterPostParam('customKey'));
            $IMSOptions->setSecretID($this->filterPostParam('secretID'));
            $IMSOptions->setSecretKey($this->filterPostParam('secretKey'));
            $IMSOptions->setCheckUrlImg($this->filterPostParam('checkUrlImg', $IMSOptions::DO_NOT_CHECK));
            self::requirePluginCenterClass();
            $staticData = self::getTencentCloudWordPressStaticData('save_config');
            TencentWordpressPluginsSettingActions::sendUserExperienceInfo($staticData);
            update_option(TENCENT_WORDPRESS_IMS_OPTIONS, $IMSOptions, true);
            wp_send_json_success(array('msg' => '保存成功'));
        } catch (Exception $exception) {
            wp_send_json_error(array('msg' => $exception->getMessage()));
        }
    }

    public function getIMSImageRecords()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('msg' => '当前用户无权限'));
        }
        $keyword = $this->filterPostParam('keyword');
        $page = $this->filterPostParam('page', 1);
        $pageSize = $this->filterPostParam('page_size', 10);
        if ($page < 1 || $page > 999999) {
            $page = 1;
        }
        if ($pageSize < 1 || $pageSize > 50) {
            $page = 10;
        }
        $pageSize = intval($pageSize);
        $page = intval($page);

        $skip = ($page - 1) * $pageSize;

        if (!empty($keyword) && !is_string($keyword)) {
            wp_send_json_error(array('msg' => '对象名称有误'));
        }
        $wpdb = $GLOBALS['wpdb'];
        $tableName = $wpdb->prefix . self::IMAGE_RECORD_TABLE_NAME;

        if (empty($keyword)) {
            $sql = "SELECT * FROM `{$tableName}` ORDER BY `id` DESC LIMIT {$skip},{$pageSize}";
            $result = $wpdb->get_results($wpdb->prepare($sql));
            //统计总条数
            $sql = "SELECT COUNT(`id`) as `count` FROM `{$tableName}`";
            $count = $wpdb->get_row($wpdb->prepare($sql));
        } else {
            $sql = "SELECT * FROM `{$tableName}` WHERE `file_name` LIKE '%s' ORDER BY `id` DESC LIMIT {$skip},{$pageSize}";
            $result = $wpdb->get_results($wpdb->prepare($sql, '%' . $keyword . '%'));
            $sql = "SELECT COUNT(`id`) as `count` FROM `{$tableName}` WHERE `file_name` LIKE '%s'";
            $count = $wpdb->get_row($wpdb->prepare($sql, '%' . $keyword . '%'));
        }
        $return = array('list' => $result, 'totalNum' => 0, 'totalPage' => 0, 'hasNext' => false);
        if (!$result) {
            wp_send_json_success($return);
        }
        $return['totalNum'] = (int)$count->count;
        $return['hasNext'] = $count->count > $pageSize * $page;
        $return['totalPage'] = intval(ceil($count->count / $pageSize));
        wp_send_json_success($return);
    }

    /**
     * 添加设置按钮
     * @param $links
     * @param $file
     * @return mixed
     */
    public function pluginSettingPageLinkButton($links, $file)
    {
        if ($file === TENCENT_WORDPRESS_IMS_BASENAME) {
            $links[] = '<a href="admin.php?page=TencentWordpressIMSSettingPage">设置</a>';
        }
        return $links;
    }

}




