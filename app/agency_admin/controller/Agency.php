<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/5
 * Time: 11:21
 */
namespace app\agency_admin\controller;
use think\Db;
class Agency extends Common
{
    protected $AgencyMemberID='';
    protected $AgencyMemberName='';
    protected $AgencyMemberInfo=array();
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->LoginCheck();
        $this->AgencyMemberInfo=Db::name('member')->where('MemberID',$this->AgencyMemberID)->find();
        $this->assign('AgencyMemberInfo',$this->AgencyMemberInfo);
        $this->assign('AgencyMemberID',$this->AgencyMemberID);
        $this->assign('AgencyMemberName',$this->AgencyMemberName);
		$this->assign('articleDomain',config("MainDomain"));
    }
    //登录时时验证
    private function LoginCheck()
    {
        $AgencyMemberID=session('AgencyMemberID');
        $AgencyMemberName=session('AgencyMemberName');
        $AgencyMemberLoginTime=session('AgencyMemberLoginTime');
        $this->AgencyMemberID=$AgencyMemberID;
        $this->AgencyMemberName=$AgencyMemberName;
        if(empty($AgencyMemberID) || empty($AgencyMemberName) || empty($AgencyMemberLoginTime)){
            $this->redirect('adminlogin/index');
            //$this->alert('','top',url('home/index'));
        }
        //if(time()-$AgencyMemberLoginTime>3600){
//            $this->error('您当前账户已连接超时，请重新登录',url('home/index'));
          //  $this->alert('您当前账户已连接超时，请重新登录','top',url('adminlogin/index'));
        //}else{
        //    session('AgencyMemberLoginTime',time());
        //}
    }
}