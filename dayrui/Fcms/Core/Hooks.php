<?php namespace Phpcmf;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * www.xunruicms.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */



/**
 * 考虑兼容继承Events
 */
class Hooks extends \CodeIgniter\Events\Events
{


    protected static $initialized_hook = false;


    /**
     * 重定义钩子类
     */
    public static function initialize()
    {
        // 防止重复加载
        if (static::$initialized_hook)
        {
            return;
        }
        require WEBPATH.'config/hooks.php';

        self::on('pre_system', function () {
            while (\ob_get_level() > 0)
            {
                \ob_end_flush();
            }

            \ob_start(function ($buffer) {
                return $buffer;
            });

            /*
             * --------------------------------------------------------------------
             * Debug Toolbar Listeners.
             * --------------------------------------------------------------------
             * If you delete, they will no longer be collected.
             */
            if (CI_DEBUG)
            {
                self::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
                \Config\Services::toolbar()->respond();
            }
        });

        static::$initialized = true;
        static::$initialized_hook = true;
    }

}

