<?php
/**
 * Created by PhpStorm.
 * User: Memberistrator
 * Date: 2018/1/4
 * Time: 9:49
 */
namespace app\admin\controller;
class Ghostwrite extends Common
{
    private $PageCount=10;
    private $MenuName='软文代写';//栏目名称
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->assign('MenuName',$this->MenuName);
    }
    public function index()
    {
        return $this->fetch();
    }
    public function edit()
    {
        if($this->request->isPost())
        {
            $Post=input('post.');
            $this->AddSave($Post);
        }else{
            return $this->fetch();
        }
    }
}