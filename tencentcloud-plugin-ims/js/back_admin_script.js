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
jQuery(function ($) {
    var ajaxUrl = $('#ims-options-form').data('ajax-url');
    //展示接口返回
    function showAjaxReturnMsg(msg,success) {
        var parent = $('#show-ims-ajax-return-msg').parent();
        if (!success) {
            parent.removeClass('alert-success');
            parent.hasClass('alert-danger') || parent.addClass('alert-danger');
        } else {
            parent.removeClass('alert-danger');
            parent.hasClass('alert-success') || parent.addClass('alert-success');
        }
        $('#show-ims-ajax-return-msg').text(msg);
        parent.show();
    }
    //关闭ajax提示
    $('#close-ims-ajax-return-msg').click(function () {
        $(this).parent().hide();
    });
    //开启关闭禁用自定义密钥
    $("#ims-option-custom-key").change(function() {
        var disabled = !($(this).is(':checked'));
        $("#txc-ims-secret-id").attr('disabled',disabled);
        $("#txc-ims-secret-key").attr('disabled',disabled);

    });
    //修改input框的type
    function changeInputType(inputElement, spanEye) {
        if(inputElement.attr('type') === 'password') {
            inputElement.attr('type','text');
            spanEye.addClass('dashicons-visibility').removeClass('shicons-hidden');
        } else {
            inputElement.attr('type','password');
            spanEye.addClass('shicons-hiddenda').removeClass('dashicons-visibility');
        }
    }
    //修改input框的type
    $('#ims-secret-id-change-type').click(function () {
        changeInputType($('#txc-ims-secret-id'), $(this));
    });
    //修改input框的type
    $('#ims-secret-key-change-type').click(function () {
        changeInputType($('#txc-ims-secret-key'), $(this));
    });
    //ajax保存配置
    $('#ims-options-update-button').click(function () {
        var secretID = $("#txc-ims-secret-id").val()
        var secretKey = $("#txc-ims-secret-key").val()
        var checkUrlImg = $("#ims-check-url-img").is(":checked")?1:0;
        var customKey = $("#ims-option-custom-key").is(":checked")?1:0
        $.ajax({
            type: "post",
            url: ajaxUrl,
            dataType:"json",
            data: {
                action: "update_IMS_options",
                secretID: secretID,
                secretKey: secretKey,
                checkUrlImg: checkUrlImg,
                customKey: customKey,
            },
            success: function(response) {
                showAjaxReturnMsg(response.data.msg,response.success)
                if (response.success){
                    setTimeout(function(){
                        //刷新当前页面.
                        window.location.reload();
                    },2000)
                }
            }
        });
    });

});