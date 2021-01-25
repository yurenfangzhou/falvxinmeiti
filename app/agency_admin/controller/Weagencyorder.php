<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/5
 * Time: 11:21
 */
namespace app\agency_admin\controller;
use think\Db;
use think\Session;
use think\Request;
use think\Loader;
use app\common\controller\simple_html_dom;
class Weagencyorder extends Common
{
    protected $AgencyMemberID='';
    protected $AgencyMemberName='';
    protected $AgencyMemberInfo=array();
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->AgencyMemberInfo=Db::name('member')->where('MemberID',$this->AgencyMemberID)->find();
        $this->assign('AgencyMemberInfo',$this->AgencyMemberInfo);
        $this->assign('AgencyMemberID',$this->AgencyMemberID);
        $this->assign('AgencyMemberName',$this->AgencyMemberName);
    }
    /**
     * 会员订单
     */
    public function member()
    {
		$UrlParam=input();
		if(isset($UrlParam['status']) && $UrlParam['status']>='0'){
			
			$where['order_state'] = $UrlParam['status'];
		}else{
			$UrlParam['status']=-1;
		}
        $this->assign('UrlParam',$UrlParam);
        $Where=array();
        $Model=new \app\agency_admin\model\Weagencyorder();
        $list=$Model->GetOrderList($UrlParam,$Where,20,$this->AgencyMemberInfo['MemberTypeID'],$this->AgencySiteInfo,1);

		$this->assign('UrlParam',$UrlParam);
		$this->assign('list',$list);

        return view();
    }
    /**
     * 代理订单
     */
    public function agent()
    {
		$UrlParam=input();
		if(isset($UrlParam['status']) && $UrlParam['status']>='0'){
			
			$where['order_state'] = $UrlParam['status'];
		}else{
			$UrlParam['status']=-1;
		}
        $this->assign('UrlParam',$UrlParam);
        $Where=array();
		$Where['agency_id']=$this->AgencyMemberInfo['MemberID'];
        $Model=new \app\agency_admin\model\Weagencyorder();
        $list=$Model->GetOrderList($UrlParam,$Where,20,$this->AgencyMemberInfo['MemberTypeID'],$this->AgencySiteInfo,3);
		$this->assign('list',$list);
        return view();
    }
    
    /**
     * 我的订单
     */
    public function home()
    {
		$UrlParam=input();
		if(isset($UrlParam['status']) && $UrlParam['status']>='0'){
			
			$where['order_state'] = $UrlParam['status'];
		}else{
			$UrlParam['status']=-1;
		}
        $Where=array();
        $Model=new \app\agency_admin\model\Weagencyorder();
        $list=$Model->GetOrderList($UrlParam,$Where,20,0,$this->AgencySiteInfo,2);
		$this->assign('UrlParam',$UrlParam);
		$this->assign('list',$list);
        return view();
    }
     //导出会员订单
    public function ExportMemberOrder()
    {
        $UrlParam=input();
        $name='会员订单';
        $Where=array();
        $Where['agency_id']=$this->AgencyMemberInfo['MemberID'];
        $Info=$this->GetMemberOrderListFull($UrlParam,$Where,$this->AgencyMemberInfo['MemberTypeID'],$this->AgencySiteInfo);
        if(!empty($Info))
        {
            //获取标题
            $TitleInfo=GetExportTitle('agencyorder');
            excelExport($name,$TitleInfo,$Info);
        }else{
            //$this->alert('导出的数据为空','back');
            $this->alert('导出数据为空','back');
            exit;
        }
    }
    public function GetMemberOrderListFull($UrlParam,$Where,$MemberTypeID,$AgencySiteInfo=array())
    {
        $Where=array_merge($UrlParam,$Where);
        $WhereArr=array();
        if(!empty($Where['title'])) $WhereArr['A.article_title|O.order_number']=['like','%'.trim($Where['title']).'%'];//文章标题
        if(!empty($Where['name'])) $WhereArr['M.MemberName']=['like','%'.trim($Where['name']).'%'];//用户名
        if(!empty($Where['date1']) || !empty($Where['date2'])){
            if(!empty($Where['date1']) && !empty($Where['date2'])){
                $Where['date1']=strtotime($Where['date1']);
                $Where['date2']=strtotime($Where['date2']);
                $begin_time=min($Where['date1'],$Where['date2']);
                $end_time=max($Where['date1'],$Where['date2']);$end_time+=86399;
                $WhereArr['O.order_time']=['between',[$begin_time,$end_time]];
            }else if(!empty($Where['date1']) && empty($Where['date2'])){
                $Where['date1']=strtotime($Where['date1'].' 00:00:00');
                $WhereArr['O.order_time']=['>=',$Where['date1']];
            }else if(empty($Where['date1']) && !empty($Where['date2'])){
                $Where['date2']=strtotime($Where['date2'].' 23:59:59');
                $WhereArr['O.order_time']=['<=',$Where['date2']];
            }
        }
        if(isset($Where['status']) && $Where['status']>='0')$WhereArr['O.order_state']=$Where['status'];//状态
        if(!empty($Where['media_name']))$WhereArr['MD.MediaTitle']=['like','%'.trim($Where['media_name']).'%'];
        if($AgencySiteInfo['MemberTypeID'] ==3){
            $WhereArr['O.agency_id1']=$Where['agency_id'];//属于我的订单
        }else if($AgencySiteInfo['MemberTypeID'] ==2){
            $WhereArr['O.agency_id']=$Where['agency_id'];//属于我的订单
        }
        $WhereArr['O.RegDomain']=$AgencySiteInfo['Domain'];//属于我的订单
        $WhereArr['A.member_id']=['neq',$AgencySiteInfo['MemberID']];//1会员
		$WhereArr['O.media_type']=2;
        $List=Db::name('agencyorder')
            ->field('O.*,A.article_title,A.article_website,A.article_id,M.MemberName,MD.MediaTitle')
            ->alias('O')//订单表
            ->where($WhereArr)
            ->join('wearticle A','A.article_id=O.article_id','left')//订单对应的文章
            ->join('member M','M.MemberID=A.member_id','left')//文章对应的会员名称
            ->join('wemedia MD','MD.MediaID=O.media_id','left')//订单对应的媒体
            ->order('O.agencyorder_id Desc')
            ->select();
        $state = ['未审核','发布中','已发布','已拒稿','已取消','定时发布'];
        $Money = [2=>'order_money2',3=>'order_money1'];
        if(!empty($List)){
            foreach ($List as $Key=>$Val){
                $List[$Key]['order_time']=date("Y-m-d H:i",$Val['order_time']);
                $List[$Key]['release_time']=$Val['release_time']>0?date("Y-m-d H:i",$Val['release_time']):'';
                $List[$Key]['check']='<div class="bai"id="'.$Val['agencyorder_id'].'"  onclick="checka('.$Val['agencyorder_id'].')" ></div>';
//                $List[$Key]['push_explain'] = $Val['push_state'] > $MemberTypeID ? '提交成功':'未推送';
                $List[$Key]['push_state'] = $Val['push_state'] > $MemberTypeID ? '已推送':'未推送';
                $List[$Key]['order_state']=$state[$Val['order_state']];
                $List[$Key]['order_money']=$Val['order_money'];
            }
        }
        return $List;
    }
   //导出我的订单
    public function ExportMyOrder()
    {
        $UrlParam=input();
        $name='我的订单';
        $Where=array();
        $Where['member_id']=$this->AgencyMemberInfo['MemberID'];
        $Info=$this->GetMediaOrderList($UrlParam,$Where,'',0,$this->AgencySiteInfo);
        if(!empty($Info))
        {
            //if(empty($UrlParam['export'])) $this->alert('导出的数据为空','back');
            //获取标题
            $TitleInfo=GetExportTitle('agencymediaorder');
            excelExport($name,$TitleInfo,$Info);
        }else{
            $this->alert('导出的数据为空','back');
           // return json(['code'=>1,'msg'=>'导出数据为空']);
            exit;
        }
    }
    public function GetMediaOrderList($UrlParam,$Where,$PageCount,$MemberTypeID,$AgencySiteInfo=array())
    {
        $Where=array_merge($UrlParam,$Where);
        $WhereArr=array();
        if(!empty($Where['title'])) $WhereArr['A.article_title|O.order_number']=['like','%'.trim($Where['title']).'%'];//文章标题
        if(!empty($Where['name'])) $WhereArr['M.MemberName']=['like','%'.trim($Where['name']).'%'];//用户名
        if(!empty($Where['date1']) || !empty($Where['date2'])){
            if(!empty($Where['date1']) && !empty($Where['date2'])){
                $Where['date1']=strtotime($Where['date1']);
                $Where['date2']=strtotime($Where['date2']);
                $begin_time=min($Where['date1'],$Where['date2']);
                $end_time=max($Where['date1'],$Where['date2']);$end_time+=86399;
                $WhereArr['O.order_time']=['between',[$begin_time,$end_time]];
            }else if(!empty($Where['date1']) && empty($Where['date2'])){
                $Where['date1']=strtotime($Where['date1'].' 00:00:00');
                $WhereArr['O.order_time']=['>=',$Where['date1']];
            }else if(empty($Where['date1']) && !empty($Where['date2'])){
                $Where['date2']=strtotime($Where['date2'].' 23:59:59');
                $WhereArr['O.order_time']=['<=',$Where['date2']];
            }
        }
        if(isset($Where['status']) && $Where['status']>='0')$WhereArr['O.order_state']=$Where['status'];//状态
        if(!empty($Where['media_name']))$WhereArr['MD.MediaTitle']=['like','%'.trim($Where['media_name']).'%'];
        if(!empty($Where['article_title']))$WhereArr['A.article_title']=['like','%'.trim($Where['article_title']).'%'];
        if($AgencySiteInfo['MemberTypeID'] ==3){
            if(!empty($Where['member_id']))$WhereArr['A.agency_member_id']=$Where['member_id'];
        }else if($AgencySiteInfo['MemberTypeID'] ==2){
            if(!empty($Where['member_id']))$WhereArr['A.agency_member_id']=$Where['member_id'];
        }else{
            if(!empty($Where['member_id']))$WhereArr['A.member_id']=$Where['member_id'];
        }
        $WhereArr['O.RegDomain']=$AgencySiteInfo['Domain'];//属于我的订单
        $WhereArr['M.GroupID']=2;//2代理商
		$WhereArr['O.media_type']=2;
        $List=db('agencyorder')
            ->field('O.*,A.article_title,A.article_website,A.article_id,M.MemberName,MD.MediaTitle')
            ->alias('O')//订单表
            ->where($WhereArr)
            ->join('wearticle A','A.article_id=O.article_id','left')//订单对应的文章
            ->join('member M','M.MemberID=A.member_id','left')//文章对应的会员名称
            ->join('wemedia MD','MD.MediaID=O.media_id','left')//订单对应的媒体
            ->order('O.agencyorder_id Desc')
            ->select();
        //echo db()->getLastSql();
        $state = ['未审核','发布中','已发布','已拒稿','已取消','定时发布'];
        if(!empty($List)){
            foreach ($List as $Key=>$Val){
                $List[$Key]['order_time']=date("Y-m-d H:i",$Val['order_time']);
                $List[$Key]['release_time']=$Val['release_time']>0?date("Y-m-d H:i",$Val['release_time']):'';
                $List[$Key]['check']='<div class="bai"id="'.$Val['agencyorder_id'].'"  onclick="checka('.$Val['agencyorder_id'].')" ></div>';
                $List[$Key]['push_explain']=$Val['push_state']==$MemberTypeID?'未推送':'提交成功';
                $List[$Key]['push_state']=$Val['push_state']>$MemberTypeID?'已推送':'未推送';
                if($AgencySiteInfo['MemberTypeID'] ==3){
                    $List[$Key]['order_money']=$Val['order_money'];
                }else if($AgencySiteInfo['MemberTypeID'] ==2){
                    $List[$Key]['order_money']=$Val['order_money2'];
                }else{
                    $List[$Key]['order_money']=$Val['order_money'];
                }
                $List[$Key]['order_state']=$state[$Val['order_state']];
            }
        }
        return $List;
    }
    //取消稿件
    public function cancel(){
        $ID=input('ID','','abs');
        $order_state = 4;
        $fabu_site = '';
        controller('common/ZiHezi')->cancel($ID);
        $fabu_content = '';
        $Contrller=controller('common/Common');
        $Contrller->WeRefund($ID,$order_state,$fabu_site,$fabu_content);
        $this->success('操作成功！');
    }
    public function push()
    {
        return false;
        $where = [];
        //二级代理
        if($this->AgencyMemberInfo['MemberTypeID'] == 2)
        {
            $where['agency_id'] = $this->AgencyMemberID;
        }else{//一级代理
            $where['agency_id1'] = $this->AgencyMemberID;
        }
        //被推送的订单
        $list = Db::name('agencyorder')
            ->where('agencyorder_id','in',input('str'))
            ->where($where)
            ->select();
        //获取费用
        $Money = 0;
        foreach ($list as $key=>$val)
        {
            $Money+=controller('common/Common')->price($this->AgencyMemberID,$val['media_id']);
        }
		//2019/5/16 星期四
        //if($Money > $this->AgencyMemberInfo['MemberAgentBalanceCount']) return '余额不足';
        foreach ($list as $key=>$val)
        {
            //判断是否推送
            if($val['push_state'] == $this->AgencyMemberInfo['MemberTypeID'])
            {
                //开始推送
                $Money = controller('common/Common')->weprice($this->AgencyMemberID,$val['media_id']);
                Db::name('member')->where('MemberID',$this->AgencyMemberID)->setDec('MemberAgentBalanceCount',$Money);
                $xj = Db::name('member')->where('MemberID',$this->AgencyMemberID)->value('MemberAgentBalanceCount');
                $data = [];
                $data['BalanceMemberID'] = $this->AgencyMemberID;
                $data['BalanceNumber'] = $val['order_number'];
                $data['BalanceType'] = 2;
                $data['BalanceCount'] = $Money;
                $data['BalanceCurrentCount'] = $xj;
                $data['BalanceTime'] = time();
                $data['BalanceTitle'] = '自媒体推送';
                $data['BalanceMemberRecommendID'] = $this->AgencyMemberInfo['MemberRecommendID'];
                $data['BalanceMemberRecommendPath'] =  $this->AgencyMemberInfo['MemberRecommendPath'];
                Db::name('balance')->insert($data);
                $data = [];
                if($this->AgencyMemberInfo['MemberRecommendID'])
                {
                    $data['agency_id1'] = $this->AgencyMemberInfo['MemberRecommendID'];
                    $data['order_money2'] =$Money;
                    $data['push_state'] = 3;
                }else{
                    if($this->AgencyMemberInfo['MemberTypeID'] == 2)
                    {
                        $data['order_money2'] =$Money;
                    }else{
                        $data['order_money1'] =$Money;
                    }
                    $data['push_state'] = 4;
                }
                Db::name('agencyorder')->where('agencyorder_id',$val['agencyorder_id'])->update($data);
            }
        }
        return '已推送';
    }

/**
     * 我的媒体订单
     */
    public function myorder()
    {
        session('MenuCookie','2');
        $UrlParam=input();
        $where=array();
        if(!empty($UrlParam['order'])) $where['A.order_number'] = ['like','%'.$UrlParam['order'].'%'];
        if(!empty($UrlParam['title'])) $where['W.article_title'] = ['like','%'.$UrlParam['title'].'%'];
		if(!empty($UrlParam['article_customer'])) $where['W.article_customer'] = ['like','%'.$UrlParam['article_customer'].'%'];
		
        if(isset($UrlParam['status']) && $UrlParam['status']>='0'){
			
			$where['A.order_state'] = $UrlParam['status'];
		}else{
			$UrlParam['status']=-1;
		}
        $StartDate = input('startdate', '');
        $EndDate = input('enddate', '');
        if(!empty($StartDate) || !empty($EndDate)){
            if(empty($StartDate)) $StartDate=date('Y-m-d');
            if(empty($EndDate)) $EndDate=date('Y-m-d');
        }
        if(!empty($StartDate) && !empty($EndDate)){
            $StartDate=strtotime($StartDate);$EndDate=strtotime($EndDate);
            $MinDate=min($StartDate,$EndDate);$MaxDate=max($StartDate,$EndDate);
            $MaxDate+=86399;
            $UrlParam['startdate']=date('Y-m-d',$MinDate);
            $UrlParam['enddate']=date('Y-m-d',$MaxDate);
            $UrlParam['MinDate']=$MinDate;$UrlParam['MaxDate']=$MaxDate;
        }
        if(!empty($UrlParam['MinDate']) && !empty($UrlParam['MaxDate'])){
            $where['A.order_time']=array('between',[$UrlParam['MinDate'],$UrlParam['MaxDate']]);
        }
        $where['A.RegDomain']=$this->AgencySiteInfo['Domain'];
		$where['A.media_type']=2;
		$where['M.MediaMemberID']=$this->AgencyMemberID;
        $List =  Db::name('agencyorder')
            ->alias('A')
            ->field('A.*,W.article_title,W.article_customer,W.article_remarks,M.MediaTitle,M.MediaMemberID')
            ->join('wearticle W', 'W.article_id=A.article_id', 'left')
            ->join('wemedia M', 'M.MediaID=A.media_id', 'left')
            ->where($where)
            ->order('A.article_id desc')
            ->paginate(20);
			$_SESSION['UrlParamOrder']=$UrlParam;
			$this->assign('UrlParam',$UrlParam);
			$this->assign('list',$List);
			return view();
    }
	/**
     * 收稿
     */
    public function collect(){
        $ordernum=input('ordernum');
        $data['order_state']= 1;
		$result=Db::name('agencyorder')->where('order_number',$ordernum)->update($data);
		if($result){
			return json(array('code'=>'200','msg'=>'收稿成功！'));
		}else{
			return json(array('code'=>'201','msg'=>'收稿失败！'));
		}
    }

	/**
     * 发布
    */
    public function publish(){

        $ordernum=input('ordernum');
        $data['order_state']= 2;
		$data['fabu_site']= input('url');

		$preg = "/^http(s)?:\\/\\/.+/";
		if(!preg_match($preg,$data['fabu_site']))
		{
			return json(array('code'=>'201','msg'=>'网址必须带http:// 或 https://'));
		}
		$result=Db::name('agencyorder')->where('order_number',$ordernum)->update($data);
		if($result){
			return json(array('code'=>'200','msg'=>'发布成功！'));
		}else{
			return json(array('code'=>'201','msg'=>'发布失败！'));
		}
    }
	/**
     * 拒稿
    */
    public function refuse(){
		$ID=input('orderid');
        $order_state= 3;
		$fabu_site='';
		$fabu_content= input('reason');
		$Contrller=controller('common/Common');
		$result=$Contrller->weRefund($ID,$order_state,$fabu_site,$fabu_content);
		if($result){
			return json(array('code'=>'200','msg'=>'拒稿成功！'));
		}else{
			return json(array('code'=>'201','msg'=>'拒稿失败！'));
		}
    }


	//文章修改
	public function edit(){
			$ID = input('id');
			if($ID){
				$ArticleInfo=db('wearticle')->where('article_id',$ID)->where('member_id',$this->AgencyMemberID)->find();
				$title = $ArticleInfo['article_title'];
				$beizhu = $ArticleInfo['article_remarks'];
				$editorValue = $ArticleInfo['article_content'];
			}else{
				$this->error('文章不存在');
			}
			$this->assign('editorValue',$editorValue);
			$this->assign('beizhu',$beizhu);
			$this->assign('title',$title);
			$this->assign('id',$ID);
  			return $this->fetch();
		}

		//保存修改
	public function editsave(){
			$data = input();
			$res='';
			if($data && $data['article_id']>0){
				$res=Db::name('wearticle')->where('article_id',$data['article_id'])->update($data);
 			}
  			if($res){
				$this->success('修改成功','weagencyorder/myorder');
			}else{
				$this->error('修改失败','weagencyorder/myorder');
			}
		}
}