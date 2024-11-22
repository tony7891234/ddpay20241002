<?php

namespace App\Admin\Actions;

use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Admin;

class BatchProcessAction extends BatchAction
{
    protected $title = '批量处理';

    public function handle(array $keys)
    {
        // 这里可以处理选中的记录
        $str = '';
        foreach ($keys as $id) {
//            $user = User::find($id);
            $str = $str . ',' . $id;
            // 在这里添加您的处理逻辑，例如更新用户状态、发送邮件等
            // $user->update([...]);
        }

//        return $this->response()->success('批量处理成功！')->refresh();
        return $this->response()->success($str)->refresh();
    }
}
