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
    var pageSize = 10;
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

    $('#sub-tab-ims-settings').click(function () {
        $('#sub-tab-ims-settings').removeClass('active').addClass('active');
        $('#sub-tab-img-records').removeClass('active');

        $('#body-sub-tab-ims-settings').addClass('active show');
        $('#body-sub-tab-img-records').removeClass('active show');
    });

    $('#sub-tab-img-records').click(function () {
        $('#sub-tab-img-records').removeClass('active').addClass('active');
        $('#sub-tab-ims-settings').removeClass('active');

        $('#body-sub-tab-img-records').addClass('active show');
        $('#body-sub-tab-ims-settings').removeClass('active show');

        $("#more_list  tr:not(:first)").remove();
        getImsData(1, pageSize)
        $('#ims_current_page')[0].innerText = '1';
        $('#ims_record_previous_page').removeClass('disabled').addClass('disabled');
        $('#ims_record_previous_page').removeAttr('disabled');
        $('#ims_record_next_page').removeAttr('disabled');
        $('#ims_record_next_page').removeClass('disabled');
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

    //命中记录
    $('#search_ims_record_button').click(function () {
        $("#more_list  tr:not(:first)").remove();
        getImsData(1,pageSize)
        $('#ims_current_page')[0].innerText = '1';
        $('#ims_record_previous_page').removeClass('disabled').addClass('disabled');
        $('#ims_record_previous_page').removeAttr('disabled');
        $('#ims_record_next_page').removeAttr('disabled');
        $('#ims_record_next_page').removeClass('disabled');
    });

    //获取上一页
    $('#ims_record_previous_page').click(function () {
        if ($(this).attr('disabled') === 'disabled') {
            return;
        }
        var currentPage = $(this).attr('data-current-page');
        if (currentPage === '1' || currentPage < '1') {
            return;
        }
        $("#more_list  tr:not(:first)").remove();
        currentPage--
        getImsData(currentPage,pageSize);
        $('#ims_current_page')[0].innerText = currentPage;
        $(this).attr('data-current-page',currentPage);
        $('#ims_record_next_page').removeAttr('disabled');
        $('#ims_record_next_page').removeClass('disabled');
    });

    //获取下一页
    $('#ims_record_next_page').click(function () {
        if ($(this).attr('disabled') === 'disabled') {
            return;
        }
        var currentPage = $('#ims_record_previous_page').attr('data-current-page');
        currentPage++
        $("#more_list  tr:not(:first)").remove();
        getImsData(currentPage,pageSize);
        $('#ims_current_page')[0].innerText = currentPage;
        $('#ims_record_previous_page').removeAttr('disabled');
        $('#ims_record_previous_page').attr('data-current-page',currentPage).removeClass('disabled');
    });

    //ajax获取短信记录
    function getImsData(page,pageSize){
        var filename = $("#search_file_name").val()
        $.ajax({
            type: "post",
            url: ajaxUrl,
            dataType:"json",
            data: {
                action: "get_ims_records",
                keyword: filename,
                page:page,
                page_size:pageSize
            },
            success: function(response) {
                var list = response.data.list;
                var html = '';
                var status = '';
                if (!response.success) {
                    alert(response.data.msg);
                    return;
                }
                //填充短信记录表格
                $.each(list, function(i, item) {
                    html += '<tr>';
                    html += '<td>' +item['user_login']+'('+ item['user_nicename'] +')'+'</td>';
                    html += '<td>' +item['user_email']+'</td>';
                    html += '<td>' +item['user_role']+'</td>';
                    html += '<td>' +item['status']+'</td>';
                    html += '<td>' +item['type']+'</td>';
                    html += '<td>' +item['create_time']+'</td>';
                    html += '<td>' +item['file_name']+'</td>';
                    html += '</tr>';
                });
                $('#more_list').append(html);

                if (page <= 1) {
                    $("#ims_record_previous_page").attr('disabled','disabled').addClass('disabled');
                }
                if (!response.data.hasNext){
                    $("#ims_record_next_page").attr('disabled','disabled').addClass('disabled');
                }
            }
        });
    }

});