<?php

namespace App\Models;

/**
 * 所有的 http 请求日志，不管是请求还是被请求
 * 所有的请求都是记录在这里，不一定有订单号  请求费率的接口失败记录，也要记录在这里  成功了就不记录了
 * Class WithdrawHttpLog
 * @package App\Models
 * @property int log_id
 * @property int type 状态:1=商户请求;2=异步回调商户;3=同步回调商户;4=请求通道;5=上游的异步回调;6=上游的同步回调
 * @property string client_ip 请求IP
 * @property string local_order_id 本平台的订单号
 * @property string out_order_id 本平台的订单号
 * @property int error_code 错误代码:可正可负
 * @property string error_message 错误信息
 * @property string content 请求或被请求的内容 json 格式
 * @property string response 上游返回数据 json 格式
 * @property string created_at
 */
class WithdrawHttpLog extends BaseModel
{
    protected $table = 'withdraw_http_log';
    protected $primaryKey = 'log_id';
    protected $fillable = [];
    protected $guarded = ['log_id'];
    protected $hidden = [];


    // 类型
    const TYPE_MERCHANT_REQUEST = 1;
    const TYPE_MERCHANT_NOTIFY = 2;
    const TYPE_MERCHANT_CALLBACK = 3;
    const TYPE_UP_PLATFORM_REQUEST = 4;
    const TYPE_UP_PLATFORM_NOTIFY = 5;
    const TYPE_UP_PLATFORM_CALLBACK = 6;
    const TYPE_NO_LOCAL_ORDER = 7;

    // 其他参数最好定义的数字比较大，以免以后的业务中需要更多的情况处理，会需要其他的数字
    const LIST_TYPE = [
        0 => '无类型',  // 这个必须写，不然控制器中遇到没有的值 会报错  默认是传递0的，但是正常的插入数据，是不会有这条数据的
        self::TYPE_MERCHANT_REQUEST => '商户请求',
        self::TYPE_MERCHANT_NOTIFY => '异步回调商户',
        self::TYPE_MERCHANT_CALLBACK => '同步回调商户',
        self::TYPE_UP_PLATFORM_REQUEST => '请求通道',
        self::TYPE_UP_PLATFORM_NOTIFY => '通道的异步回调',
        self::TYPE_UP_PLATFORM_CALLBACK => '通道异步回调',
        self::TYPE_NO_LOCAL_ORDER => '没有匹配到本地订单号',
    ];

    /**
     * 获取状态代号对应的名字
     * @return string
     */
    public function getTypeName()
    {
        if (array_key_exists($this->type, self::LIST_TYPE)) {
            return self::LIST_TYPE[$this->type];
        }
        return '未知类型:' . $this->type;
    }


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->log_id;
    }


}
