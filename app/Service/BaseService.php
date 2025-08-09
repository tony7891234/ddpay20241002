<?php

namespace App\Service;


use App\Models\User;
use App\Traits\RepositoryTrait;

/**
 * Class BaseService
 * @package App\Service
 */
class BaseService
{

    use RepositoryTrait;

    /**
     * @var int
     */
    protected $uid;

    /**
     * @var int
     */
    protected $game_id;

    /**
     * @var User
     */
    protected $userInfo;

    /**
     * 错误代码
     * @var int
     */
    protected $errorCode;

    /**
     * 错误信息
     * @var string
     */
    protected $errorMessage = '';


    /**
     * 返回错误代码
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * 返回错误信息
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

}
