<?php

namespace App\Repository;

/**
 * Class BaseRepository
 * @package App\Repository
 */
class BaseRepository
{

    /**
     * @var int
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|static
     */
    public $model;
    protected $withCount;
    protected $with;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {

    }

    /**
     * 创建操作
     * @param $array array
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create($array)
    {
        return $this->model->create($array);
    }

    /**
     * 批量插入操作
     * @param $array
     * @return bool
     */
    public function insert($array)
    {
        return $this->model->insert($array);
    }

    /**
     * 更新操作
     * @param $array array
     * @return bool
     */
    public function update($array)
    {
        return $this->model->update($array);
    }

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
     * 设置预加载项
     * @param $with string|array
     * @return $this
     */
    public function setWith($with)
    {
        $this->with = $with;
        return $this;
    }

    /**
     * 获取预加载项
     * @return string|array
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * 设置预加载项
     * @param $with string|array
     * @return $this
     */
    public function setWithCount($with)
    {
        $this->withCount = $with;
        return $this;
    }

    /**
     * 获取预加载项
     * @return string|array
     */
    public function getWithCount()
    {
        return $this->withCount;
    }

}
