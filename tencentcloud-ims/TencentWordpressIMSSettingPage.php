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
use TencentWordpressIMS\TencentWordPressIMSActions;

function TencentWordpressIMSSettingPage()
{
    $ajaxUrl = admin_url('admin-ajax.php');
    $IMSOptions = TencentWordPressIMSActions::getIMSOptionsObject();
    $secretID = $IMSOptions->getSecretID();
    $secretKey = $IMSOptions->getSecretKey();
    $checkUrlImg = $IMSOptions->getCheckUrlImg();
    $customKey = $IMSOptions->getCustomKey();

    ?>
    <style type="text/css">
        .dashicons {
            vertical-align: middle;
            position: relative;
            right: 30px;
        }
    </style>
    <div id="message" class="updated notice is-dismissible" style="margin-bottom: 1%;margin-left:0;"><p>
            腾讯云图片内容安全（IMS）插件启用生效中。</p>
        <button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button>
    </div>
    <div class="bs-docs-section">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-header ">
                    <h1 id="forms">腾讯云图片内容安全（IMS）插件</h1>
                </div>
                <p>对用户提交的图片出现违规涉黄、爆、恐的内容，进行内容检测和过滤</p>
            </div>
        </div>
        <div class="alert alert-dismissible alert-success" style="display: none;">
            <button type="button" id="close-ims-ajax-return-msg" class="close" data-dismiss="alert">&times;</button>
            <div id="show-ims-ajax-return-msg">操作成功.</div>
        </div>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="javascript:void(0);" id="sub-tab-ims-settings">插件配置</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0);" id="sub-tab-img-records">图片检查记录</a>
            </li>
        </ul>
        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade active show" id="body-sub-tab-ims-settings">
                <div class="postbox">
                    <div class="inside">
                        <div class="row">
                            <div class="col-lg-9">
                                <form method="post" id="ims-options-form" action="" data-ajax-url="<?php echo $ajaxUrl ?>">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-2 lable_padding_left" for="ims-option-custom-key"><h5>自定义密钥</h5></label>
                                        <div class="custom-control custom-switch div_custom_switch_padding_top"
                                             style="margin-top: -2.3rem;margin-left: 13rem;">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="ims-option-custom-key" <?php if ( $customKey === $IMSOptions::CUSTOM_KEY ) {
                                                echo 'checked';
                                            } ?> >
                                            <label class="custom-control-label"
                                                   for="ims-option-custom-key">为该插件配置单独定义的腾讯云密钥</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-2" for="txc-ims-secret-id"><h5>SecretId</h5></label>
                                        <input id="txc-ims-secret-id" type="password" class="col-lg-5 is-invalid"
                                               placeholder="SecretId" <?php if ( $customKey !== $IMSOptions::CUSTOM_KEY ) {
                                            echo 'disabled="disabled"';
                                        } ?>
                                               value="<?php echo $secretID; ?>">
                                        <span id="ims-secret-id-change-type" class="dashicons dashicons-hidden"></span>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-2" for="txc-ims-secret-key"><h5>SecretKey</h5></label>
                                        <input id="txc-ims-secret-key" type="password" class="col-lg-5 is-invalid"
                                               placeholder="SecretKey" <?php if ( $customKey !== $IMSOptions::CUSTOM_KEY ) {
                                            echo 'disabled="disabled"';
                                        } ?>
                                               value="<?php echo $secretKey ?>">
                                        <span id="ims-secret-key-change-type" class="dashicons dashicons-hidden"></span>
                                        <div class="offset-lg-2">
                                            <p class="description">访问 <a href="https://console.qcloud.com/cam/capi"
                                                                         target="_blank">密钥管理</a>获取
                                                SecretId和SecretKey或通过"新建密钥"创建密钥串</p>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-form-label col-lg-2 lable_padding_left" for="ims-check-url-img"><h5>审核URL图片</h5></label>
                                        <div class="custom-control custom-switch div_custom_switch_padding_top"
                                             style="margin-top: -2.3rem;margin-left: 13rem;">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="ims-check-url-img" <?php if ( $checkUrlImg === $IMSOptions::CHECK_URL_IMG ) {
                                                echo 'checked';
                                            } ?> >
                                            <label class="custom-control-label"
                                                   for="ims-check-url-img">写文章时通过第三方URL插入的图片</label>
                                        </div>
                                        <p class="description" style="margin-left: 13rem;">
                                            默认不检测。开启插件后，发布文章中的每个URL图片都将被审核，产生延时等待。酌情开启！</p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <button id="ims-options-update-button" type="button" class="btn btn-primary">保存配置</button>
            </div>

            <div class="tab-pane fade active" id="body-sub-tab-img-records">
                <div class="inside postbox">
                    <div class="col-lg-12">
                        <form method="post" id="tms-options-form" action="" data-ajax-url="<?php echo $ajaxUrl ?>">
                            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                                <span class="navbar-brand">对象名称</span>
                                <button class="navbar-toggler" type="button" data-toggle="collapse"
                                        data-target="#navbarColor03"
                                        aria-controls="navbarColor03" aria-expanded="false"
                                        aria-label="Toggle navigation">
                                    <span class="navbar-toggler-icon"></span>
                                </button>
                                <div class="collapse navbar-collapse" id="navbarColor03" style="max-width: 35%;">
                                    <form class="form-inline my-2 my-lg-0">
                                        <input class="form-control  mr-sm-2" type="text" id="search_file_name">
                                        <button class="btn" style="width: 5rem;" type="button"
                                                id="search_ims_record_button">搜索
                                        </button>
                                    </form>
                                </div>
                            </nav>
                            <div class="inside table-responsive">
                                <table id="tms-whitelist-table" class="table table-hover" style="table-layout:fixed">
                                    <tbody id="more_list">
                                    <tr class="table-primary">
                                        <th>用户名(昵称)</th>
                                        <th>邮箱地址</th>
                                        <th>角色类型</th>
                                        <th>状态</th>
                                        <th>检查对象</th>
                                        <th>发布时间</th>
                                        <th style="width: 25%">对象名称</th>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div style="float: right;">
                                <ul class="pagination">
                                    <li class="page-item disabled" id="ims_record_previous_page" data-current-page="1">
                                        <a class="page-link" href="javascript:void(0);">&laquo;</a>
                                    </li>
                                    <li class="page-item active">
                                        <a class="page-link" id="ims_current_page" href="javascript:void(0);">1</a>
                                    </li>
                                    <li class="page-item" id="ims_record_next_page">
                                        <a class="page-link" href="javascript:void(0);">&raquo;</a>
                                    </li>
                                </ul>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <div style="text-align: center;flex: 0 0 auto;margin-top: 3rem;">
            <a href="https://openapp.qq.com/docs/Wordpress/ims.html" target="_blank">文档中心</a> | <a href="https://github.com/Tencent-Cloud-Plugins/tencentcloud-wordpress-plugin-ims" target="_blank">GitHub</a> | <a
                    href="https://da.do/y0rp" target="_blank">意见反馈</a>
        </div>
    </div>
<?php
}