<?php

namespace App\Models;


/**
 * 批量出款
 * Class BatchWithdraw
 * @package App\Models
 * @property int bach_id
 * @property int merchant_id
 * @property int batch_no  批次   对应 sms_orders.batch_no
 * @property string message  内容
 * @property string file  文件路径
 * @property int created_at  添加时间
 * @property int updated_at  更新时间(接收处理)
 * @property string response_success  成功得
 * @property string response_fail  失败得
 */
class BatchWithdraw extends BaseModel
{
    protected $table = 'cd_batch_withdraw';
    protected $primaryKey = 'bach_id';
    protected $fillable = [];
    protected $guarded = ['bach_id'];
    protected $hidden = [];


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->bach_id;
    }


}
