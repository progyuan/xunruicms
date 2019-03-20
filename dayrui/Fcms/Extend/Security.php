<?php
namespace Phpcmf\Extend;

/**
 * 用于Services.php
 */

use CodeIgniter\HTTP\RequestInterface;

class Security extends \CodeIgniter\Security\Security
{

    public function CSRFVerify(RequestInterface $request)
    {

        if (defined('IS_API') && IS_API) {
            return $this;
        } elseif (isset($_GET['appid']) && isset($_GET['appsecret'])) {
            return $this;
        } elseif (APP_DIR == 'weixin') {
            return $this;
        }

        return parent::CSRFVerify($request);
    }

}