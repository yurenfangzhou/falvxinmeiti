<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/22
 * Time: 9:40
 */
namespace app\admin\controller;
use think\Db;
use \app\admin\model\Helptype as HelpTypeModel;
class Helptype extends Common
{
    private $MenuName = '帮助中心分类';
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->assign('FileSeat','/'.config('config.FileSeat').'/');
        $this->assign('MenuName',$this->MenuName);
    }
    //列表数据
    public function index()
    {
        $UrlParam=input('get.');
        $this->assign('UrlParam',$UrlParam);
        $Keyword = input('keyword', '');
        $List = $this->ParentTypeList($Keyword);
        $this->assign('List', $List);
        return $this->fetch();
    }
    //添加数据
    public function add()
    {
        if($this->request->isPost())
        {
            $Data=input('post.');
            $this->AddSave($Data);
//            $this->success('添加成功');
            $this->alert('添加成功','back');
            exit;
        }else{
            //获取父级分类列表
            //dump($this->ParentTypeList());
            $this->assign('ParentTypeList',$this->ParentTypeList());
            return $this->fetch();
        }
    }
    //保存数据
    private function AddSave($Data)
    {
        //处理 id node path 的相关参数
        $ParentID=$Data['TypePID'];
        $Node='0';$Path='';
        if($ParentID=='0')
        {
            $ParentID='0';
            $Node='1';
            $Path='';
        }else{
            $ParentIDArr=explode('_',$ParentID);
            $ParentID=abs($ParentIDArr[0]);
            $Node=abs($ParentIDArr[1])+1;
            $Path=trim($ParentIDArr[2]);
        }
        $Data['TypePID']=$ParentID;
        $Data['TypeNode']=$Node;
        $Data['TypeTime']=time();
        //保存添加的数据
        Db::name('helptype')->data($Data)->insert();
        $InsertID=Db::getLastInsID();
        //更新 path 路径
        $Where=[];
        $Where['TypeID']=$InsertID;
        $SaveData=[];
        $SaveData['TypePath']=$Path.$InsertID.',';
        Db::name('helptype')->where($Where)->update($SaveData);
        //保存日志
        AdminLogDetail(['LogOperateTitle'=>$this->MenuName.'数据添加','LogOperateContent'=>'ID:'.$InsertID]);
    }
    //编辑数据
    public function edit()
    {
        if($this->request->isPost())
        {
            $Data=input('post.');
            $ID=input('post.TypeID');
            $this->EditSave($ID,$Data);
        }else{
            $ID=input('param.id','','intval');
            $Info=Db::name('helptype')->where(['TypeID'=>$ID])->find();
            $this->assign('ParentTypeList',$this->ParentTypeList('','Edit',$Info['TypeID']));
            $this->assign('Info',$Info);
            return $this->fetch();
        }
    }
    //修改保存数据
    private function EditSave($ID,$Data)
    {
        //dump($ID);
        //dump($Data);
        $OldTypePath=$Data['TypePath'];
        //更新当前层级
        $TypePID=$Data['TypePID'];
        if($TypePID=='0')
        {
            $NewTypePath=$ID.',';
            $Data['TypePID']='0';
            $Data['TypeNode']='1';
            $Data['TypePath']=$NewTypePath;
        }else{
            $TempArr=explode('_',$TypePID);
            $NewTypePath=$TempArr[2].$ID.',';
            $Data['TypePID']=$TempArr[0];
            $Data['TypeNode']=$TempArr[1]+1;
            $Data['TypePath']=$NewTypePath;
            //if($Data['parentid']==$Data['id'])
            //{
            //$this->error('您的当前级别不可挪到当前级别');
            //}
            //dump($NewPath);
            //dump($Data['id']);
            if(stristr($TempArr[2],$Data['TypeID'].',')!==false)
            {
               // $this->error('您的当前级别不可挪到小于您的当前级别之下');
                $this->alert('您的当前级别不可挪到小于您的当前级别之下','back');
                exit;
            }
        }
        Db::name('helptype')->data($Data)->where('TypeID',$ID)->update();
        //更新当前层级的所有子集
        $List=Db::name('helptype')->field('TypeID,TypePath')->where('TypePath','like',$OldTypePath.'%')->select();
        if(!empty($List))
        {
            $UpdateData=array();
            foreach ($List as $key=>$val)
            {
                $TempPath=str_replace($OldTypePath,$NewTypePath,$val['TypePath']);
                $UpdateData[]=['TypeID'=>$val['TypeID'],'TypePath'=>$TempPath,'TypeNode'=>substr_count($TempPath,',')];
            }
            $Model=new HelpTypeModel();
            $Model->saveAll($UpdateData);
        }
        AdminLogDetail(['LogOperateTitle'=>$this->MenuName.'数据修改成功','LogOperateContent'=>'ID:'.$ID]);
//        $this->success('更新成功',url('index'));
        $this->alert('更新成功','jump',url('index'));
        exit;
    }
    //获取父分分类 $State 修改时当前级别之下的 子集则不显示
    public function ParentTypeList($Keyword='',$State='',$CurrentID='',$PID='0',$TempArr=array(),$Seat='')
    {
        $Where=[];
        if(empty($Keyword))
        {
            $Where['TypePID']=$PID;
            if($State=='Edit')
            {
                //dump($CurrentID);
                //dump($PID);
                if($CurrentID==$PID)
                {
                    unset($TempArr[$CurrentID]);
                    //dump($TempArr);
                    return $TempArr;
                }
            }
        }else{
            $Where['TypeName']=['like','%'.$Keyword.'%'];
        }
        //->field('id,title,node,path')
        $List=Db::name('helptype')->where($Where)->order('TypeSort asc,TypeID asc')->select();
        if(empty($List))
        {
            return $TempArr;
        }else{
            if(empty($Keyword))
            {
                foreach ($List as $key=>$val)
                {
                    $val['OldTypeName']=$val['TypeName'];
                    $val['TypeName']=$Seat.$val['TypeName'];
                    $TempArr[$val['TypeID']]=$val;
                    $TempArr=$this->ParentTypeList($Keyword,$State,$CurrentID,$val['TypeID'],$TempArr,$Seat.'　　');
                }
            }else{
                $TempArr=$List;
            }
            return $TempArr;
        }
    }
    //批量排序
    public function sort()
    {
        $SortArr=input('post.Sort/a');
        //dump($SortArr);
        if(!empty($SortArr))
        {
            $TempArr=[];$SortID=[];$SortIDStr='';
            foreach ($SortArr as $key=>$val)
            {
                $SortID[]=$key;
                $TempArr[]=['TypeID'=>$key,'TypeSort'=>$val];
            }
            if(!empty($SortID)){$SortIDStr=implode(',',$SortID);}
            $Model=new HelpTypeModel();
            $Model->saveAll($TempArr);
            AdminLogDetail(['LogOperateTitle'=>$this->MenuName.'批量排序成功','LogOperateContent'=>'ID:'.$SortIDStr]);
        }
//        $this->success('排序更新成功');
        $this->alert('排序更新成功','back');
        exit;
    }
    //单记录删除数据
    public function del()
    {
        if($this->request->isPost())
        {
            $this->delmore();
        }elseif($this->request->isGet()) {
            $ID = input('param.id');
            //dump($ID);
            if (!empty($ID)) {
                $Info = Db::name('helptype')->field('TypePath')->where('TypeID', $ID)->find();
                if (empty($Info)) {
//                    $this->error('此记录已删除，请不要重复操作');
                    $this->alert('此记录已删除，请不要重复操作','back');
                    exit;
                }
                $TypePath = $Info['TypePath'];
                if (empty($TypePath)) {
                    $TypePath = '-1';
                } else {
                    $TypePath .= '%';
                }
                //获取批量删除的ID值
                $DelIDList = Db::name('helptype')
                    ->field('TypeID')
                    ->where(['TypeID' => $ID])
                    ->whereOr('TypePath', 'like', $TypePath)//''.%   %
                    ->order('TypeSort asc,TypeID asc')
                    ->select();
                $DelIDTemp = [];
                $DelIDTempStr = '';
                if (!empty($DelIDList)) {
                    foreach ($DelIDList as $key => $val) {
                        $DelIDTemp[] = $val['TypeID'];
                    }
                    $DelIDTempStr = implode(',', $DelIDTemp);
                }
                Db::name('helptype')
                    ->where(['TypeID' => $ID])
                    ->whereOr('TypePath', 'like', $TypePath)
                    ->delete();
                AdminLogDetail(['LogOperateTitle' => $this->MenuName . '此记录已删除成功', 'LogOperateContent' => 'ID:' . $DelIDTempStr]);
            }
//            $this->success('此记录已删除成功');
            $this->alert('此记录已删除成功','back');
            exit;
        }
    }
    //批量删除记录
    private function delmore()
    {
        $DelID=input('post.Del/a');
        if(!empty($DelID))
        {
            $TempArrID=[];
            $TempArrPath=[];
            foreach ($DelID as $key=>$val)
            {
                $Arr=explode('_',$val);
                $TempArrID[]=$Arr[0];
                if(empty($Arr[1])){
                    $TempArrPath[]='-1';
                }else{
                    $TempArrPath[]=$Arr[1].'%';
                }
            }
            $Where=[];
            $DelIDStr=implode(',',$TempArrID);//52,53
            //$Where['id']=['in',$DelIDStr];
            //$Where['path']=['like',$val[1].'%'];
            //获取批量删除的ID值
            $DelIDList=Db::name('helptype')
                ->field('TypeID')
                ->where('TypeID','in',$DelIDStr)
                ->whereOr('TypePath','like',$TempArrPath,'or')
                ->order('TypeSort asc,TypeID asc')
                ->select();
            //dump($DelIDList);
            if(empty($DelIDList)){
//                $this->error('此记录已删除，请不要重复操作');
                $this->alert('此记录已删除，请不要重复操作','back');
                exit;
            }
            $DelIDTemp=[];$DelIDTempStr='';
            if(!empty($DelIDList)) {
                foreach ($DelIDList as $key => $val)
                {
                    $DelIDTemp[]=$val['TypeID'];
                }
                $DelIDTempStr=implode(',',$DelIDTemp);
            }
//            dump($DelIDTempStr);
            //'id','in',$DelIDStr
            Db::name('helptype')
                ->where('TypeID','in',$DelIDStr)
                ->whereOr('TypePath','like',$TempArrPath,'or')
                ->delete();
//            dump($DelIDList);
            AdminLogDetail(['LogOperateTitle'=>$this->MenuName.'批量删除成功','LogOperateContent'=>'ID:'.$DelIDTempStr]);
        }
//        $this->success('此记录批量删除成功');
        $this->alert('此记录批量删除成功','back');
        exit;
    }
}