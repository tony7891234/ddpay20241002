<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseModel
 *  所有的 Model 备注，除了id 使用　getId()  方法备注，其他的都只是属性，最好标注下
 * @package App\Models
 * @property \Carbon\Carbon created_at
 * @property \Carbon\Carbon updated_at
 * @property \Carbon\Carbon deleted_at
 * @mixin \Eloquent
 */
class BaseModel extends Model
{

    const PREFIX_BATCH_WITHDRAW = 3; // 批量出款
    const PREFIX_WITHDRAW_ORDER = 4; // 单条出款

    //  不要自动维护时间字段
    public $timestamps = false; // 添加的时候，不自动使用 created_at;更新不自动使用 updated_at

    /**
     * 添加时间
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * 修改时间
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updated_at->format('Y-m-d H:i:s');
    }

    /**
     * 删除时间
     * @return string
     */
    public function getDeletedAt()
    {
        return $this->deleted_at->format('Y-m-d H:i:s');
    }


}
