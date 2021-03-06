<?php
/**
 * Created by PhpStorm.
 * User: Memberistrator
 * Date: 2018/1/4
 * Time: 10:54
 */
namespace app\agency_admin\model;
use think\Model;
use think\Db;
class Videomedia extends Model
{
    //视频自媒体列表分页
    public function GetMediaList($UrlParam,$Where,$PageCount,$MemberInfo,$AgencyMemberID,$AgencySiteInfo,$pricelevel=1)
    {

        $Where = array_merge($UrlParam, $Where);
        $WhereArr = array();
        $OrderStr='';
		$Release=1;
        $arr = explode(',',@$UrlParam['where']);		
        foreach ($arr as $key=>$val){
            if($val){
                $lsarr = explode('-',$val);
				
                if($lsarr[0]!='PriceNum'&&$lsarr[0]!='OrderWay'){
                    if($lsarr[1] != 0) $WhereArr[$lsarr[0]] = $lsarr[1];
                }else if($lsarr[0]=='PriceNum'){
                    if($lsarr[1] != 0){
						 if($lsarr[1]==1){
							$WhereArr['MediaMemberPrice'.$pricelevel]=['<=',50];
						}else if($lsarr[1]==2){
							$WhereArr['MediaMemberPrice'.$pricelevel]=[['>=',51],['<=',200]];;
						}else if($lsarr[1]==3){
							$WhereArr['MediaMemberPrice'.$pricelevel]=[['>=',201],['<=',501]];;
						}else if($lsarr[1]==4){
							$WhereArr['MediaMemberPrice'.$pricelevel]=[['>',501],['<=',1000]];;
						}else if($lsarr[1]==5){
							$WhereArr['MediaMemberPrice'.$pricelevel]=[['>=',1001],['<=',2001]];;
						}else if($lsarr[1]==6){
							$WhereArr['MediaMemberPrice'.$pricelevel]=[['>',2001],['<=',5000]];;
						}else if($lsarr[1]==7){
							$WhereArr['MediaMemberPrice'.$pricelevel]=['>',5000];
						}
                    }
                }else if($lsarr[0]=='OrderWay'){
                    $Order=$lsarr[1];
                    if($Order==1){
                       $OrderStr=" M.Recommend desc,M.MediaMemberPrice".$pricelevel." asc";
                    }elseif($Order==11){
						$OrderStr=" M.Recommend desc,M.MediaMemberPrice".$pricelevel." desc";
					}elseif($Order==2){
						$OrderStr=" M.Recommend desc,M.Fennumid asc";
					}elseif($Order==21){
						$OrderStr=" M.Recommend desc,M.Fennumid desc";
					}
                }else{
					$OrderStr=" M.Recommend desc,M.MediaID asc";
				}
				if(!empty($Where['Platformtype'])) $WhereArr['M.Platformtype']=$lsarr[1];//平台类型
				if(!empty($Where['classification'])) $WhereArr['M.classification']=$lsarr[1];//行业分类
				if(!empty($Where['Coverage'])) $WhereArr['M.Coverage']=$lsarr[1];//地区
				if(!empty($Where['Fennumid'])) $WhereArr['M.Fennumid']=$lsarr[1];
				if(!empty($Where['Numbercount'])) $Where['M.Numbercount']=$lsarr[1];
				if(!empty($Where['Auth'])) $WhereArr['M.Auth']=$lsarr[1];				
				if(!empty($Where['inclusion'])) $WhereArr['M.inclusion']=$lsarr[1];//收录情况
				if(!empty($Where['whether'])) $WhereArr['M.Auth']=$lsarr[1];//是否探店
				if(!empty($Where['Merchandise'])) $WhereArr['M.Merchandise']=$lsarr[1];//商品橱窗
				if(!empty($Where['Gender'])) $WhereArr['M.Gender']=$lsarr[1];//性别
				//if(!empty($Where['Release'])) $Release=$lsarr[1];//性别
				if($lsarr[0]=='Release') $Release=$lsarr[1];//发布类型 1 直发 2原创
				unset($WhereArr['Release']);
				
            }
        }
		if (!empty($Where['media_name'])) $WhereArr['M.MediaTitle'] = ['like','%'.trim($Where['media_name']).'%'];
        $WhereArr['M.MediaState'] = 1;
        if(!empty($UrlParam['collect']) && $UrlParam['collect']=='list'){
			$WhereArr['W6.CollectMemberID']=$MemberInfo['MemberID'];
            $WhereArr['W6.CollectAgencyDomainID']=$AgencySiteInfo['MemberID'];		
			$WhereArr['W6.ctype']=3;
			$List = $this
                ->field('M.*,W2.TypeName as TypeName2,W3.TypeName as TypeName3,W4.TypeName as TypeName4')
                ->alias('M')
                ->where($WhereArr)
				->join('__WHOLETYPE__ W2','M.Platformtype=W2.TypeID','left')
                ->join('__WHOLETYPE__ W3','M.classification=W3.TypeID','left')
				->join('__CITYTYPE__ W4','M.Coverage=W4.TypeID','left')
				->join('collect W6', 'W6.CollectGoodsID=M.MediaID', 'left')//收藏
				->order($OrderStr)
                ->paginate(20, false, [
                    'query' => $UrlParam,
                ]);
        }else{
            $List = $this
                ->field('M.*,W2.TypeName as TypeName2,W3.TypeName as TypeName3,W4.TypeName as TypeName4')
                ->alias('M')
                ->where($WhereArr)
                 ->join('__WHOLETYPE__ W2','M.Platformtype=W2.TypeID','left')
                ->join('__WHOLETYPE__ W3','M.classification=W3.TypeID','left')
				->join('__CITYTYPE__ W4','M.Coverage=W4.TypeID','left')
				->order($OrderStr)
                ->paginate($PageCount, false, [
                    'query' => $UrlParam
                ]);
        }
		//echo $this->getlastsql();
		//die;
        if (!empty($List)) {
            foreach ($List as $key=>$val)
            {
				if($val['TypeName4']==''){
                   $List[$key]['TypeName4']='综合全国';
                }
				$List[$key]['TypeName5']=ArrayCheck($val['Fennumid'],'FanNum');
				$List[$key]['TypeName6']=ArrayCheck($val['Numbercount'],'ReadNum');
                //价格及选择！
				if($Release==1){
					 $List[$key]['pricetitle'] = '直发';
				}elseif($Release==2){
					 $List[$key]['pricetitle'] = '原创';
				}elseif($Release==3){
					 $List[$key]['pricetitle'] = '直播';
				}
                $price = controller('common/Common')->videoprice($MemberInfo['MemberID'],$val['MediaID'],$Release);
                $List[$key]['check'] = '<div class="bai" pic="'.$price.'" id="'.$val['MediaID'].'" name="'.$val['MediaTitle'].'" onclick="checka('.$val['MediaID'].')" pic="'.$price.'"></div>';
                $List[$key]['price'] = $price;
                //入口链接
                $InLink='';
                if(!empty($val['MediaInUrl'])){
                    $InLink='<a href=\''.$val['MediaInUrl'].'\' target=\'_blank\' class=\'tips\' data-tips=\'入口仅供参考\' style=\'color:red;font-size:12px\'  title="提示:入口仅供参考,不代表一定是此入口或者一定有入口">（入口）</a>';
                }
				$List[$key]['MediaTitle'] = $val['MediaTitle'].$InLink;
				if(!empty(Getuserface($val['MediaMemberID']))){
					$List[$key]['MediaTitle'].='<div class="info_pic am-hide-sm-only" style="background-image: url('.Getuserface($val['MediaMemberID']).')"></div>';
				}
				if($val['Recommend']==1){
					$List[$key]['MediaTitle'].='<span style="background: url(/static/assets/i/recommend.gif) center center no-repeat;padding:0 7px;"></span>';
				}
				
				//收藏
                $WhereArr=array();
                $WhereArr['CollectMemberID']=$MemberInfo['MemberID'];
                $WhereArr['CollectAgencyDomainID']=$AgencySiteInfo['MemberID'];
                $WhereArr['CollectGoodsID']=$val['MediaID'];
                $State=db('collect')->where($WhereArr)->find();
                if($State){
                    $List[$key]['mediaid_checks']='<a class=\'Media'.$val['MediaID'].' ClassStyle2\' onclick=\'CollectMedia('.$val['MediaID'].')\' ></a>';
                }else{
                    $List[$key]['mediaid_checks']='<a class=\'Media'.$val['MediaID'].' ClassStyle1\' onclick=\'CollectMedia('.$val['MediaID'].')\' ></a>';
                }
            }
        }
        return $List;
    }
	//查询会员自己的视频自媒体资源
	public function GetMemberMediaList($UrlParam,$Where,$PageCount,$MemberID)
		{
			 $List = $this
                ->field('V.*,C.TypeName as Coverage,W1.TypeName as Platformname,W2.TypeName as classificationname')
                ->alias('V')
                ->where('MediaMemberID','eq',$MemberID)
				->where($Where)
                ->join('citytype C', 'C.TypeID=V.Coverage', 'left')//所在区域
                ->join('wholetype W1', 'W1.TypeID=V.Platformtype', 'left')//平台类型
                ->join('wholetype W2', 'W2.TypeID=V.classification', 'left')//行业分类
                 ->order('V.MediaID Desc')
                ->paginate($PageCount, false, ['query' => $UrlParam]);
 				return $List;
		}
}