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

    /**
     * @param $uid
     * @return $this
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
        return $this;
    }


    /**
     * 设置游戏id 参数
     * @param $game_id
     * @return $this
     */
    public function setGameId($game_id)
    {
        $this->game_id = $game_id;
        return $this;
    }

    /**
     * 设置 userInfo
     * @param $userInfo
     * @return $this
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
        return $this;
    }

    /**
     * @return User
     */
    public function getUserInfo()
    {
        if ($this->userInfo) {
            return $this->userInfo;
        }
        $this->userInfo = User::where('id', '=', $this->uid)->first();
        return $this->userInfo;
    }


    /**
     * 检查用户是否登录
     * @param $token
     * @return bool
     */
    public function isLogin($token)
    {
        $this->userInfo = $this->getUserInfo();
        if (!$this->userInfo) {
            $this->errorCode = -1;
            $this->errorMessage = 'Account does not exist!';
            return false;
        }

        if ($token != $this->userInfo['token']) {
            $this->errorCode = -1008;
            $this->errorMessage = 'Login first!';
            return false;
        }

        // 验证账号状态
        if ($this->userInfo['status'] == User::STATUS_LOCK) {
            $this->errorCode = -4;
            $this->errorMessage = 'Account is blocked!';
            return false; // account is blocked!
        }

        return $response = true;
    }

}
