<?php

namespace App\Traits;

/**
 * 所有的仓库数据
 * 这个在 BaseService 中有引用，如果其他的 Repository 中需要使用，则直接引用即可
 * Trait RepositoryTrait
 * @package App\Traits
 */
trait RepositoryTrait
{



    /**
     * @return \App\Repository\TelegramRepository
     */
    protected function getTelegramRepository()
    {
        static $repository;
        if ($repository) {
            return $repository;
        }
        return $repository = app('App\Repository\TelegramRepository');
    }



}
