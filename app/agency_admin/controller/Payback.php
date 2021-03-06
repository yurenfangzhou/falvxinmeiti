<?php
namespace app\agency_admin\controller;
use think\Controller;
use think\Config;
use think\Db;
class Payback extends Controller
{
    private $AgencySiteInfo=array();
    private $Config=array();
    private $SessionMemberID='';
    private $SessionMemberName='';
    private $SessionAgencyMemberID='';
    private $SessionAgencyMemberName='';
    public function _initialize(){
        $Controller=controller('common/Common');
        $GetCurrentDomainInfo=$Controller->GetCurrentDomainInfo();
        //dump($GetCurrentDomainInfo);
        $this->AgencySiteInfo=$GetCurrentDomainInfo['SiteInfo'];
        $this->Config=$GetCurrentDomainInfo['Config'];
        $this->assign('Config',$this->Config);
        $this->SessionMemberID=session('MemberID');
        $this->SessionMemberName=session('MemberName');
        $this->assign('SessionMemberID',$this->SessionMemberID);
        $this->assign('SessionMemberName',$this->SessionMemberName);
        $this->SessionAgencyMemberID=session('AgencyMemberID');
        $this->SessionAgencyMemberName=session('AgencyMemberName');
        $this->assign('SessionAgencyMemberID',$this->SessionAgencyMemberID);
        $this->assign('SessionAgencyMemberName',$this->SessionAgencyMemberName);
    }
    public function alipay_notify()
    {
        //file_put_contents('55.txt',var_export($_POST,true));
        $System=Config::get('systemparam');
//        dump($this->AgencySiteInfo);
//        dump($System);
//        exit;
        AliPayApiNotifyCall($this->AgencySiteInfo,$System,1,'admin.php');
	}
    
    public function alipay_successhtml(){
        //dump(input('get.'));
        $this->assign('OrderInfo',input('get.'));
        return $this->fetch();
    }
}