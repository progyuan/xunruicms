<?php namespace Phpcmf\Extend;

/**
 * 用于Services.php
 */

class Request extends \CodeIgniter\HTTP\IncomingRequest
{
    protected function parseRequestURI(): string
    {
        return '/'; // 这里要固定返回 / 确保cms自定义URL正常使用
    }
}