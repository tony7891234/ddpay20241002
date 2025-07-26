<?php

namespace App\Models;

/**
 * @property  int id
 * @property  string account 50  账号
 * @property  int status  状态:1=正确;2=错误;3=不一致
 * @property  string remark 255  错误的原因
 * @property  string content  text  返回的内容
 * @property  int add_time  添加时间
 */
class PixModel extends BaseModel
{

    protected $connection = 'rds';
    protected $table = 'cd_pix';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $hidden = [];


    /**
     * 自增ID
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    protected $name = 'pix';

    const STATUS_SUCCESS = 1;
    const STATUS_BLOCK = 2;
    const STATUS_WRONG_ACCOUNT = 3;
    const STATUS_INVALID_PIX_ENTRY = 4; // "Invalid Pix Entry"  EG: 02310523520180061991752   7.11 号 treeal 银行加的
    const STATUS_INVALID_PIX_WRONG = 5;
    const LIST_STATUS = [
        self::STATUS_SUCCESS => '成功',
        self::STATUS_BLOCK => '拉黑',
        self::STATUS_WRONG_ACCOUNT => '账号不一致',
        self::STATUS_INVALID_PIX_ENTRY => 'Invalid Pix Entry',
        self::STATUS_INVALID_PIX_WRONG => 'Chave Pix não encontrada',
    ];


}
