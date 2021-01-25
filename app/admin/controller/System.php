<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/12
 * Time: 12:08
 */
namespace app\admin\controller;
use think\Db;
use think\Cache;
class System extends Common
{
    //公共的方法调用
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->assign('FileSeat','/'.config('config.FileSeat').'/');
    }
    public function clearing()
    {
        if($this->request->isPost())
        {
            $Post=input('post.');
            $this->ClearingSave($Post);
        }else{
            $FilePath=APP_PATH.'extra/systemparam.php';
            $SystemParam=[];
            if(is_file($FilePath)){
                $SystemParam=include $FilePath;
            }
            $this->assign('SystemParam',$SystemParam);
            $CouponsMoney=config('couponsmoeny');
            $this->assign('CouponsMoney',$CouponsMoney);
            $this->assign('MenuName','系统参数配置');
            return $this->fetch();
        }
    }
    private function ClearingSave($Post)
    {
        $Str='<?php'."\r\n";
        $Str.='return ';
        $Str.=var_export($Post,true);
        $Str.=';';
        file_put_contents(APP_PATH.'extra/systemparam.php',$Str);
        AdminLogDetail(['LogOperateTitle'=>'系统参数配置更新成功']);
        //$this->success('初始化参数配置数据更新成功');
        $this->alert('系统参数配置更新成功','back');
        exit;
    }
    public function website()
    {
        if($this->request->isPost()){
            $Post=input('post.');
            $this->WebSiteSave($Post);
        }else{
            $WebSite=Db::name('config')->where(['ID'=>'1','MemberID'=>'-1'])->find();
            $this->assign('WebSite',$WebSite);
            $this->assign('MenuName','站点参数配置');
            return $this->fetch();
        }
    }
    private function WebSiteSave($Post)
    {
        //图片上传
        $PicPath=$this->OneUpload('WebSiteLogo');
        $PicPath2=$this->OneUpload2('WebSiteMusic');
        if(!empty($PicPath)) $Post['WebSiteLogo']=$PicPath;
        if(!empty($PicPath2)) $Post['WebSiteMusic']=$PicPath2;
        $Count=Db::name('config')->where(['ID'=>'1'])->count();
        if(empty($Count)){
            $Post['ID']='1';$Post['MemberID']='-1';
            Db::name('config')->data($Post)->insert();
        }else{
            $Post['ID']='1';$Post['MemberID']='-1';
            Db::name('config')->data($Post)->update();
        }
        AdminLogDetail(['LogOperateTitle'=>'站点参数配置数据更新成功']);
        $this->alert('站点参数配置数据更新成功','back');
        exit;
    }
}