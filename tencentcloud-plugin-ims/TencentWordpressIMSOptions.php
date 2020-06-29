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
class TencentWordpressIMSOptions
{
    //使用全局密钥
    const GLOBAL_KEY = 0;
    //使用自定义密钥
    const CUSTOM_KEY = 1;
    const DO_NOT_CHECK = 0;
    const CHECK_URL_IMG = 1;
    private $secretID;
    private $secretKey;
    private $checkUrlImg;
    private $customKey;

    public function __construct($customKey=self::GLOBAL_KEY,$secretID='',$secretKey='',$checkUrlImg=self::DO_NOT_CHECK)
    {
        $this->customKey = $customKey;
        $this->secretID = $secretID;
        $this->secretKey = $secretKey;
        $this->checkUrlImg = $checkUrlImg;
    }

    /**
     * 获取全局的配置项
     */
    public function getCommonOptions()
    {
        return get_option(TENCENT_WORDPRESS_COMMON_OPTIONS);
    }

    public function setSecretID($secretID)
    {
        if (empty($secretID)) {
            throw new \Exception('secretID不能为空');
        }
        $this->secretID = $secretID;
    }

    public function setSecretKey($secretKey)
    {
        if (empty($secretKey)) {
            throw new \Exception('secretKey不能为空');
        }
        $this->secretKey = $secretKey;
    }

    public function setCustomKey($customKey)
    {
        if (!in_array($customKey,array(self::GLOBAL_KEY,self::CUSTOM_KEY))) {
            throw new \Exception('自定义密钥传参错误');
        }
        $this->customKey = intval($customKey);
    }

    public function setCheckUrlImg($checkUrlImg)
    {
        if (!in_array($checkUrlImg,[self::DO_NOT_CHECK,self::CHECK_URL_IMG])) {
            throw new \Exception('检查url的传参错误');
        }
        $this->checkUrlImg = intval($checkUrlImg);
    }

    public function getSecretID()
    {
        $commonOptions = $this->getCommonOptions();
        if ($this->customKey === self::GLOBAL_KEY && isset($commonOptions['secret_id'])) {
            $this->secretID = $commonOptions['secret_id']?:'';
        }
        return $this->secretID;
    }

    public function getSecretKey()
    {
        $commonOptions = $this->getCommonOptions();
        if ($this->customKey === self::GLOBAL_KEY && isset($commonOptions['secret_key'])) {
            $this->secretKey = $commonOptions['secret_key']?:'';
        }
        return $this->secretKey;
    }

    public function getCheckUrlImg()
    {
        return $this->checkUrlImg;
    }

    public function getCustomKey()
    {
        return $this->customKey;
    }

}