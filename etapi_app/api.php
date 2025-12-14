<?php
error_reporting(E_ALL); 
ini_set('display_errors', 0); 
header('Content-Type: application/json');

// --- КОНСТАНТЫ ID СМАРТ-ПРОЦЕССОВ ---
define('SP_STAGES', 1290); 
define('SP_INCOME', 1310); 
define('SP_EXPENSES', 1304); 
define('SP_SUBCONTRACTORS', 1266); 
define('SP_RATES', 1318);

// --- ПОЛЯ ПРИВЯЗКИ ---
define('LINK_STAGE_DEAL', 'ufCrm86_1763852237'); // Этап -> Сделка
define('LINK_STAGE_SUB', 'ufCrm86_1764881202');  // Этап -> Субподрядчик

define('LINK_INCOME', 'ufCrm94_1763844594');     // Приход -> Сделка
define('LINK_EXPENSE', 'ufCrm92_1763844515');    // Расход -> Субподрядчик

// --- ПОЛЯ ДАННЫХ ---
define('FLD_PAID_INCOME', 'ufCrm94_1763844352'); 
define('FLD_PAID_EXPENSE', 'ufCrm92_1763844404'); 
define('FLD_STAGE_STATUS', 'ufCrm86_1763860705');

// --- ПОЛЯ НАСТРОЕК НЕУСТОЕК ---
define('FLD_PEN_RISK_TYPE', 'UF_CRM_1763912787'); 
define('FLD_PEN_RISK_VAL', 'UF_CRM_1763912810'); 
define('FLD_PEN_RISK_BASE', 'UF_CRM_1763912857');
define('FLD_PEN_PROF_TYPE', 'UF_CRM_1763912796'); 
define('FLD_PEN_PROF_VAL', 'UF_CRM_1763912817'); 
define('FLD_PEN_PROF_BASE', 'UF_CRM_1763912863');

// --- БАЗОВЫЕ ФУНКЦИИ ---

function callBitrix($m, $p=[]) { 
    if(!empty($_REQUEST['auth']['access_token'])) { 
        $u='https://'.$_REQUEST['auth']['domain'].'/rest/'.$m.'.json'; 
        $p['auth']=$_REQUEST['auth']['access_token']; 
    } else return ['error'=>'No Auth']; 
    $ch=curl_init(); curl_setopt($ch,CURLOPT_URL,$u); curl_setopt($ch,CURLOPT_POST,1); 
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($p)); curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false); curl_setopt($ch,CURLOPT_HTTPHEADER,['Content-Type: application/json']); 
    $r=curl_exec($ch); curl_close($ch); return json_decode($r,true); 
}

function callBatch($c) { return callBitrix('batch',['cmd'=>$c,'halt'=>0]); }

// Строгая проверка ID
function isIdMatch($v, $t) { 
    $target = (string)$t;
    if(is_array($v)) { foreach($v as $val) if((string)$val === $target) return true; return false; }
    return (string)$v === $target; 
}

// Кэширование курсов ЦБ
function getRatesFromSP() { 
    $f=__DIR__.'/sp_rates_cache.json'; 
    if(file_exists($f)&&(time()-filemtime($f)<3600)) return json_decode(file_get_contents($f),true); 
    $r=callBitrix('crm.item.list',['entityTypeId'=>SP_RATES,'select'=>['ufCrm98_1763910905','ufCrm98_1763910912'],'order'=>['ufCrm98_1763910905'=>'ASC'],'limit'=>1000]); 
    $res=[]; 
    if(isset($r['result']['items'])) foreach($r['result']['items'] as $i) if($i['ufCrm98_1763910905']) $res[]=['date'=>substr($i['ufCrm98_1763910905'],0,10),'rate'=>(float)str_replace(',','.',trim($i['ufCrm98_1763910912']))]; 
    file_put_contents($f,json_encode($res)); return $res; 
}

// --- ОСНОВНАЯ ЛОГИКА ---

try { 
    if(!empty($_POST['auth'])) $_REQUEST['auth']=$_POST['auth']; 
    $act=$_POST['action']??''; 
    $eid=(int)($_POST['entityId']??0); 
    $type=$_POST['entityType']??'DEAL';

    // 1. КУРСЫ
    if($act==='getRatesFromSP'){ echo json_encode(getRatesFromSP()); exit; }
    
    // 2. ПОЛУЧЕНИЕ ТОВАРОВ (РАБОЧАЯ ВЕРСИЯ ПРИ НАЛИЧИИ ПРАВ CATALOG)
    if($act==='getProducts'){ 
        $rawProducts = [];

        if($type==='DEAL') {
            $r = callBitrix('crm.deal.productrows.get', ['id' => $eid]); 
            $rawProducts = $r['result'] ?? [];
        } else { 
            // Для Смарт-процессов (Субподрядчики, 1266)
            // Обязательно запрашиваем SELECT PRODUCT_ROWS
            $r = callBitrix('crm.item.get', [
                'entityTypeId' => SP_SUBCONTRACTORS,
                'id' => $eid,
                'select' => ['ID', 'TITLE', 'OPPORTUNITY', 'PRODUCT_ROWS']
            ]);
            
            if(isset($r['result']['item']['productRows'])) {
                $rawProducts = $r['result']['item']['productRows'];
            }
        }

        // НОРМАЛИЗАЦИЯ (Приводим к виду PRODUCT_ID / PRODUCT_NAME)
        $normalizedProducts = [];
        foreach($rawProducts as $p) {
            // Поддержка разных регистров (productId / PRODUCT_ID)
            $pId = $p['productId'] ?? $p['PRODUCT_ID'] ?? $p['id'] ?? 0;
            $pName = $p['productName'] ?? $p['PRODUCT_NAME'] ?? $p['product_name'] ?? 'Без названия';
            
            if($pId > 0) {
                $normalizedProducts[] = [
                    'PRODUCT_ID' => $pId,
                    'PRODUCT_NAME' => $pName
                ];
            }
        }
        
        echo json_encode($normalizedProducts); 
        exit;
    }
    
    // 3. ЗАГРУЗКА ДАННЫХ
    if($act==='loadExistingItems') {
        $linkFieldStage = ($type === 'DEAL') ? LINK_STAGE_DEAL : LINK_STAGE_SUB;
        
        $sStr=['id','title','opportunity', $linkFieldStage, FLD_STAGE_STATUS,'ufCrm86_1763846855','ufCrm86_1763846958','ufCrm86_1763847030','ufCrm86_1763846816','ufCrm86_1763846858','ufCrm86_1763846867','ufCrm86_1763846940','ufCrm86_1763846881','ufCrm86_1763845210'];
        $pStr=['id','title','opportunity',$type=='DEAL'?LINK_INCOME:LINK_EXPENSE,'ufCrm20_1741452390','ufCrm94_1763847173','ufCrm94_1763847186','ufCrm94_1763847202','ufCrm94_1763847205','ufCrm94_1763847210','ufCrm94_1763847218','ufCrm92_1763847390','ufCrm92_1763847413','ufCrm92_1763847432','ufCrm92_1763847442','ufCrm92_1763847449','ufCrm92_1763847458',FLD_PAID_INCOME,FLD_PAID_EXPENSE];
        
        $penConfig = ['risk'=>['type'=>'fixed','val'=>'0.1','base'=>'item'], 'profit'=>['type'=>'fixed','val'=>'0.1','base'=>'item']];
        if($type === 'DEAL') { 
            $d = callBitrix('crm.deal.get', ['ID'=>$eid]); 
            if(isset($d['result'])) { 
                $penConfig['risk'] = [ 'type' => $d['result'][FLD_PEN_RISK_TYPE] ?? 'fixed', 'val' => $d['result'][FLD_PEN_RISK_VAL] ?? '0.1', 'base' => $d['result'][FLD_PEN_RISK_BASE] ?? 'item' ]; 
                $penConfig['profit'] = [ 'type' => $d['result'][FLD_PEN_PROF_TYPE] ?? 'fixed', 'val' => $d['result'][FLD_PEN_PROF_VAL] ?? '0.1', 'base' => $d['result'][FLD_PEN_PROF_BASE] ?? 'item' ]; 
            } 
        }

        $b=[
            's'=>['method'=>'crm.item.list','params'=>['entityTypeId'=>SP_STAGES,'filter'=>[$linkFieldStage=>$eid],'select'=>$sStr]],
            'p'=>['method'=>'crm.item.list','params'=>['entityTypeId'=>$type=='DEAL'?SP_INCOME:SP_EXPENSES,'filter'=>[$type=='DEAL'?LINK_INCOME:LINK_EXPENSE=>$eid],'select'=>$pStr]]
        ];
        
        $br=callBatch($b); 
        $sf=$br['result']['result']['s']??[]; 
        $pf=$br['result']['result']['p']??[];
        
        // Fallback
        if(empty($sf)) { 
            $r=callBitrix('crm.item.list',['entityTypeId'=>SP_STAGES,'order'=>['id'=>'DESC'],'limit'=>50,'select'=>$sStr]); 
            if(isset($r['result']['items'])) foreach($r['result']['items'] as $i) if(isIdMatch($i[$linkFieldStage],$eid)) $sf[]=$i; 
        }
        if(empty($pf)) { 
            $r=callBitrix('crm.item.list',['entityTypeId'=>$type=='DEAL'?SP_INCOME:SP_EXPENSES,'order'=>['id'=>'DESC'],'limit'=>50,'select'=>$pStr]); 
            if(isset($r['result']['items'])) foreach($r['result']['items'] as $i) if(isIdMatch($i[$type=='DEAL'?LINK_INCOME:LINK_EXPENSE],$eid)) $pf[]=$i; 
        }
        
        $res=[]; $ids=[];
        foreach($sf as $r) { 
            if(in_array('s'.$r['id'],$ids))continue; $ids[]='s'.$r['id']; 
            $pr=[]; if(!empty($r['ufCrm86_1763845210'])) foreach(explode(' || ',$r['ufCrm86_1763845210']) as $x){$y=explode(':',$x); if(count($y)>1) $pr[]=['id'=>$y[0],'name'=>$y[1]];} 
            $res[]=['id'=>$r['id'],'isPayment'=>false,'sort'=>$r['ufCrm86_1763846816'],'name'=>$r['ufCrm86_1763846855'],'eventType'=>$r['ufCrm86_1763846858'],'termType'=>$r['ufCrm86_1763846867'],'duration'=>$r['ufCrm86_1763846940'],'dateStart'=>substr($r['ufCrm86_1763846958'],0,10),'dateEnd'=>substr($r['ufCrm86_1763847030'],0,10),'linkedStages'=>$r['ufCrm86_1763846881'],'amount'=>$r['opportunity'],'products'=>$pr,'status'=>$r[FLD_STAGE_STATUS]??'']; 
        }
        foreach($pf as $r) { 
            if(in_array('p'.$r['id'],$ids))continue; $ids[]='p'.$r['id']; 
            $pd=$type=='DEAL'?($r[FLD_PAID_INCOME]??''):($r[FLD_PAID_EXPENSE]??''); 
            $d=['id'=>$r['id'],'isPayment'=>true,'amount'=>$r['opportunity'],'products'=>[],'paidData'=>$pd]; 
            if($type=='DEAL') $d+=['sort'=>$r['ufCrm94_1763847173'],'name'=>$r['ufCrm94_1763847186'],'eventType'=>'payment','termType'=>$r['ufCrm94_1763847202'],'duration'=>$r['ufCrm94_1763847205'],'dateStart'=>substr($r['ufCrm94_1763847210'],0,10),'dateEnd'=>substr($r['ufCrm20_1741452390'],0,10),'linkedStages'=>$r['ufCrm94_1763847218']];
            else $d+=['sort'=>$r['ufCrm92_1763847390'],'name'=>$r['ufCrm92_1763847413'],'eventType'=>'payment','termType'=>$r['ufCrm92_1763847432'],'duration'=>$r['ufCrm92_1763847442'],'dateStart'=>substr($r['ufCrm92_1763847449'],0,10),'dateEnd'=>substr($r['ufCrm20_1741452390'],0,10),'linkedStages'=>$r['ufCrm92_1763847458']];
            $res[]=$d; 
        }
        usort($res,function($a,$b){return (int)$a['sort']-(int)$b['sort'];}); 
        echo json_encode(['result'=>$res, 'penConfig'=>$penConfig]); exit;
    }

    // 4. СОХРАНЕНИЕ НАСТРОЕК
    if($act==='saveSettings') { 
        $s = json_decode($_POST['settings'], true); 
        if($type === 'DEAL') { 
            $f = [ FLD_PEN_RISK_TYPE => $s['risk']['type'], FLD_PEN_RISK_VAL => $s['risk']['val'], FLD_PEN_RISK_BASE => $s['risk']['base'], FLD_PEN_PROF_TYPE => $s['profit']['type'], FLD_PEN_PROF_VAL => $s['profit']['val'], FLD_PEN_PROF_BASE => $s['profit']['base'] ]; 
            callBitrix('crm.deal.update', ['id'=>$eid, 'fields'=>$f]); 
        } 
        echo json_encode(['status'=>'ok']); exit; 
    }

    // 5. УДАЛЕНИЕ ЗАПИСИ
    if($act==='deleteItem') {
        $itemId = (int)$_POST['itemId'];
        $itemType = $_POST['itemType']; 
        
        $targetSPA = 0;
        if($itemType === 'payment') {
            if($type === 'DEAL') $targetSPA = SP_INCOME;
            else $targetSPA = SP_EXPENSES;
        } else {
            $targetSPA = SP_STAGES;
        }
        
        if($itemId > 0 && $targetSPA > 0) {
            $r = callBitrix('crm.item.delete', ['entityTypeId' => $targetSPA, 'id' => $itemId]);
            if(isset($r['error'])) echo json_encode(['error' => $r['error_description']]);
            else echo json_encode(['status' => 'deleted']);
        } else {
            echo json_encode(['error' => 'Invalid ID or Type']);
        }
        exit;
    }

    // 6. СОХРАНЕНИЕ
    if($act==='saveAll') { 
        $items=json_decode($_POST['items'],true); 
        foreach($items as $i) { 
            $new=!is_numeric($i['id']); 
            $id=$new?0:(int)$i['id']; 
            $isP=$i['eventType']=='payment'; 
            $f=['title'=>$i['name'],'opportunity'=>$i['amount']]; 
            
            if($isP) { 
                if($type=='DEAL') { 
                    $t=SP_INCOME; 
                    $f['ufCrm94_1763847194']='Оплата'; 
                    $f[LINK_INCOME]=$eid; 
                    $f['ufCrm94_1763847173']=$i['sort']; $f['ufCrm94_1763847186']=$i['name']; $f['ufCrm94_1763847202']=$i['termType']; $f['ufCrm94_1763847205']=$i['duration']; $f['ufCrm94_1763847210']=$i['dateStart']; $f['ufCrm20_1741452390']=$i['dateEnd']; $f['ufCrm94_1763847218']=$i['linkedStages']; 
                } else { 
                    $t=SP_EXPENSES; 
                    $f['ufCrm92_1763847427']='Оплата'; 
                    $f[LINK_EXPENSE]=$eid; 
                    $f['ufCrm92_1763847390']=$i['sort']; $f['ufCrm92_1763847413']=$i['name']; $f['ufCrm92_1763847432']=$i['termType']; $f['ufCrm92_1763847442']=$i['duration']; $f['ufCrm92_1763847449']=$i['dateStart']; $f['ufCrm20_1741452390']=$i['dateEnd']; $f['ufCrm92_1763847458']=$i['linkedStages']; 
                } 
            } else { 
                $t=SP_STAGES; 
                $f['ufCrm86_1763846858']=$i['eventType']; 
                if($type === 'DEAL') { $f[LINK_STAGE_DEAL] = $eid; $f[LINK_STAGE_SUB] = ''; } 
                else { $f[LINK_STAGE_SUB] = $eid; $f[LINK_STAGE_DEAL] = ''; }

                $f['ufCrm86_1763846816']=$i['sort']; $f['ufCrm86_1763846855']=$i['name']; $f['ufCrm86_1763846867']=$i['termType']; $f['ufCrm86_1763846940']=$i['duration']; $f['ufCrm86_1763846958']=$i['dateStart']; $f['ufCrm86_1763847030']=$i['dateEnd']; $f['ufCrm86_1763846881']=$i['linkedStages']; $f[FLD_STAGE_STATUS]=$i['status']; 
                if(!empty($i['products'])) { $a=[]; foreach($i['products'] as $p) $a[]=$p['id'].':'.$p['name']; $f['ufCrm86_1763845210']=implode(' || ',$a); } else $f['ufCrm86_1763845210']=''; 
            } 
            
            if($new) callBitrix('crm.item.add',['entityTypeId'=>$t,'fields'=>$f]); 
            else callBitrix('crm.item.update',['entityTypeId'=>$t,'id'=>$id,'fields'=>$f]); 
        } 
        echo json_encode(['status'=>'ok']); exit; 
    }
} catch(Exception $e) { echo json_encode(['error'=>$e->getMessage()]); }
?>