<?php
/**
 * Created by PhpStorm.
 * User: Memberistrator
 * Date: 2018/1/4
 * Time: 9:49
 */
namespace app\admin\controller;
use \app\admin\model\Article as ArticleModel;
use think\Db;
class Changedoc extends Common
{
    private $PageCount=10;
    private $MenuName='改稿列表';//栏目名称
    public function _initialize(){
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->assign('MenuName',$this->MenuName);
        $this->assign('config',db('config')->where('MemberID','eq',-1)->find());
    }
    public function index(){
        $UrlParam=input('get.');
        $UrlParam['state'] = isset($UrlParam['state']) ? $UrlParam['state'] : '';
        $this->assign('UrlParam',$UrlParam);
        $where = [];
        if(!empty($UrlParam['title'])) $where['A.articletitle'] = ['like','%'.$UrlParam['title'].'%'];
        if(!empty($UrlParam['ordernum'])) $where['A.ordernum'] = ['like','%'.$UrlParam['ordernum'].'%'];
		if(!empty($UrlParam['mphone'])) $where['M.MemberName'] = ['like','%'.trim($UrlParam['mphone']).'%'];
        if(isset($UrlParam['state']) && $UrlParam['state']>='0'){
			$where['A.state']=$UrlParam['state'];
        }
        $List =  Db::name('changedoc')
            ->alias('A')
            ->field('A.*,W.article_id,W.media_name,W.fabu_site,W.fabu_content,M.MemberName')            
			->join('agencyorder W', 'W.agencyorder_id=A.orderid', 'left')
			->join('member M', 'M.MemberID=A.memberid', 'left')
            ->where($where)
            ->order('A.addtime desc')
            ->paginate(20,true,['query'  => $UrlParam]);
		$changecount = Db::name('changedoc')->where("state",'neq',2)->count();
		$this->assign('changecount',$changecount);
        $this->assign('list',$List);
        return $this->fetch();
    }
	public function edit(){
        $ID=input('id','','abs');
		 $info =  Db::name('changedoc')
            ->alias('A')
            ->field('A.*,W.article_id,W.media_name,W.fabu_site,W.fabu_content')            
			->join('agencyorder W', 'W.agencyorder_id=A.orderid', 'left')
            ->where('A.id',$ID)
            ->order('A.addtime desc')
			->find();
        $this->assign('Info',$info);
        return $this->fetch();
    }
	/**
     * 改稿保存
    */
    public function change_editsave(){
		$UrlParam = input();
		$info=Db::name('changedoc')->where('id',$UrlParam['id'])->where('state','neq',3)->find();
		if($info){
			$data1=array(
				'state_text'=>$UrlParam['state_text'],
				'state'=>$UrlParam['state']

			);
			$result=Db::name('changedoc')->where('id',$UrlParam['id'])->update($data1);
			if($UrlParam['state']==3){
				$returndata=Db::name('Member')->where('MemberID',$info['memberid'])->setInc('MemberBalanceCount',$info['price']);
				$xj = Db::name('Member')->where('MemberID',$info['memberid'])->value('MemberBalanceCount');
				$data['BalanceMemberID'] = $info['memberid'];
				$data['BalanceNumber'] = $info['ordernum'];
				$data['BalanceType'] = 1;
				$data['BalanceCount'] = $info['price'];
				$data['BalanceCurrentCount'] = $xj;
				$data['BalanceTime'] = time();
				$data['BalanceGroup'] = '3';
				$data['BalanceState'] = '1';
				$data['BalanceTitle'] = '改稿退单';
 				$resultdd=Db::name('balance')->insertGetId($data);
			}
			if($result){
				 $this->success('处理成功！');
			}else{
				 $this->success('处理失败！');
			}
		}
		 $this->success('处理失败！');
    }
}