<?php
// index.php - Версия 88.0 (Modal CSS Fix: Fixed Layout + Date/Amount Merge)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once(__DIR__ . '/crest.php');

// === КОНФИГУРАЦИЯ ===
const SP_ID = 1314;       
const SP_EXPENSE_ID = 1304; 
const SP_INCOME_ID = 1310;  

const LIST_EXPENSE_ID = 102; 
const LIST_INCOME_ID = 108;  

const FIELDS = [
    'PAYER' => 'ufCrm30_1740692985',
    'PAYER_INN' => 'ufCrm30_1740692994',
    'PAYEE' => 'ufCrm96_1763921634',
    'PAYEE_INN' => 'ufCrm96_1763921640',
    'DEBIT' => 'ufCrm30_1740693015',     
    'CREDIT' => 'ufCrm30_1740693021',    
    'PURPOSE' => 'ufCrm30_1740693029',
    'DATE' => 'ufCrm30_1740695065',
    'DOC_NUM' => 'ufCrm30_1740695072',
    'CALC_TYPE' => 'ufCrm30_1740695990',
    'SUM_EXPENSE' => 'ufCrm30_1740696285', 
    'COMMENT' => 'ufCrm30_1740696340',
    'TOTAL_ALLOC' => 'ufCrm30_1740696906', 
    'REMAINDER' => 'ufCrm30_1740698481', 
    'LINK_EXPENSE' => 'ufCrm96_1763916881', 
    'LINK_INCOME' => 'ufCrm96_1763916899', 
    'EXPENSE_TYPE' => 'ufCrm96_1763922631', 
    'INCOME_TYPE'  => 'ufCrm96_1763935961',
    'IGNORE_FLAG' => 'ufCrm96_1763922513', 
    'JSON_SPLIT' => 'ufCrm96_1763923857',
    'SPLIT_READABLE' => 'ufCrm96_1764021781'
];

const TARGET_INCOME_FIELDS = [
    'TYPE' => 'ufCrm94_1763935908',      
    'PAYEE_1' => 'ufCrm32_1741256841',   
    'PAYEE_2' => 'ufCrm32_1741256867',   
    'PAYER' => 'ufCrm32_1741256907',     
    'LINK_IMPORT' => 'ufCrm94_1764188870', 
    'DATE' => 'ufCrm20_1741452390',      
    'INFO_STR' => 'ufCrm94_1763844352',  
    'STAGE' => 'DT1310_118:SUCCESS'      
];

const TARGET_EXPENSE_FIELDS = [
    'TYPE' => 'ufCrm92_1763983487',
    'PAYEE' => 'ufCrm20_1741426003',
    'PAYER_1' => 'ufCrm20_1738569678',
    'PAYER_2' => 'ufCrm20_1738569630',
    'LINK_IMPORT' => 'ufCrm92_1764188790', 
    'DATE' => 'ufCrm20_1741452390',
    'INFO_STR' => 'ufCrm92_1763983424',
    'STAGE' => 'DT1304_116:SUCCESS'
];

// === БЭКЕНД ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_start();
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        // 1. LOAD SELECTOR
        if ($action === 'load_selector') {
            $entityTypeId = (int)$_POST['entityTypeId'];
            $page = (int)($_POST['page'] ?? 1);
            $limit = 20;
            $start = ($page - 1) * $limit;
            $search = trim($_POST['search'] ?? '');
            $filter = [];
            $filterMode = $_POST['filter_mode'] ?? '';
            if ($filterMode === 'debit_only') $filter['>'.FIELDS['DEBIT']] = 0;
            elseif ($filterMode === 'credit_only') $filter['>'.FIELDS['CREDIT']] = 0;

            if ($search) {
                $subFilter = ['LOGIC' => 'OR', '%TITLE' => $search, '=ID' => $search];
                if (is_numeric($search)) $subFilter['=OPPORTUNITY'] = $search;
                $filter[] = $subFilter;
            }
            
            $select = ['ID', 'TITLE', 'OPPORTUNITY', 'opportunity', 'CREATED_TIME', 'ufCrm20_1741452390'];
            if ($entityTypeId == SP_ID) {
                $select[] = FIELDS['DEBIT'];
                $select[] = FIELDS['CREDIT'];
                $select[] = FIELDS['DATE'];
            }

            $res = CRest::call('crm.item.list', [
                'entityTypeId' => $entityTypeId, 
                'order' => ['id' => 'DESC'], 
                'start' => $start, 
                'filter' => $filter, 
                'select' => $select
            ]);
            
            $rawItems = $res['result']['items'] ?? []; 
            $cleanItems = [];
            foreach ($rawItems as $item) {
                $id = $item['id'] ?? $item['ID']; 
                $title = $item['title'] ?? $item['TITLE']; if (empty($title)) $title = 'Элемент #' . $id;
                $opp = $item['opportunity'] ?? $item['OPPORTUNITY'] ?? 0;
                $date = $item['ufCrm20_1741452390'] ?? $item['createdTime'] ?? $item['CREATED_TIME'] ?? '';

                if ($entityTypeId == SP_ID) {
                    $d = (float)($item[FIELDS['DEBIT']] ?? 0);
                    $c = (float)($item[FIELDS['CREDIT']] ?? 0);
                    if ($d > 0) $opp = $d; elseif ($c > 0) $opp = $c;
                    $spDate = $item[FIELDS['DATE']] ?? '';
                    if($spDate) $date = $spDate;
                }
                $cleanItems[] = ['ID' => $id, 'TITLE' => $title, 'OPPORTUNITY' => $opp, 'DATE' => $date];
            }
            $total = $res['total'] ?? 0; if($total == 0 && !empty($cleanItems)) $total = 999; 
            echo json_encode(['items' => $cleanItems, 'total' => $total, 'page' => $page]); exit;
        }

        // 2. DICTIONARIES
        if ($action === 'get_dictionaries') {
            $batch = [];
            $offsets = [0, 50, 100, 150, 200];
            foreach ($offsets as $st) {
                $batch['exp_crm_'.$st] = ['method' => 'crm.item.list', 'params' => ['entityTypeId' => LIST_EXPENSE_ID, 'start' => $st, 'order' => ['TITLE' => 'ASC'], 'select' => ['ID', 'TITLE']]];
                $batch['inc_crm_'.$st] = ['method' => 'crm.item.list', 'params' => ['entityTypeId' => LIST_INCOME_ID, 'start' => $st, 'order' => ['TITLE' => 'ASC'], 'select' => ['ID', 'TITLE']]];
                $types = ['lists', 'bitrix_processes', 'lists_socnet'];
                foreach ($types as $t) {
                    $batch['exp_ls_'.$t.'_'.$st] = ['method' => 'lists.element.get', 'params' => ['IBLOCK_TYPE_ID' => $t, 'IBLOCK_ID' => LIST_EXPENSE_ID, 'start' => $st]];
                    $batch['inc_ls_'.$t.'_'.$st] = ['method' => 'lists.element.get', 'params' => ['IBLOCK_TYPE_ID' => $t, 'IBLOCK_ID' => LIST_INCOME_ID, 'start' => $st]];
                }
            }
            $res = CRest::callBatch($batch);
            $rr = $res['result']['result'] ?? [];
            $collect = function($prefix) use ($rr) {
                $all = [];
                foreach ($rr as $key => $data) {
                    if (strpos($key, $prefix) === 0) {
                        $items = $data['items'] ?? $data ?? []; 
                        if (!is_array($items)) continue;
                        foreach ($items as $it) {
                            $name = $it['NAME'] ?? $it['name'] ?? $it['TITLE'] ?? $it['title'] ?? 'NoName';
                            $id = $it['ID'] ?? $it['id'];
                            if ($id) $all[$id] = ['ID' => $id, 'NAME' => $name];
                        }
                    }
                }
                usort($all, function($a, $b){ return strcmp($a['NAME'], $b['NAME']); });
                return array_values($all);
            };
            $expense = $collect('exp_crm_'); if(empty($expense)) $expense = $collect('exp_ls_');
            $income = $collect('inc_crm_'); if(empty($income)) $income = $collect('inc_ls_');
            echo json_encode(['expense' => $expense, 'income' => $income]); exit;
        }

        // 3-4. CREATE SP
        if ($action === 'create_expense_sp') { echo json_encode(['success' => true, 'id' => performCreateSP($_POST['id'], 'expense')]); exit; }
        if ($action === 'create_income_sp') { echo json_encode(['success' => true, 'id' => performCreateSP($_POST['id'], 'income')]); exit; }

        // 5. LOAD GRID
        if ($action === 'load_grid') {
            $page = (int)($_POST['page'] ?? 1); $limit = 50; $start = ($page - 1) * $limit;
            $filter = []; $type = $_POST['filter_type'] ?? '';
            if ($type === 'expense') $filter['>'.FIELDS['CREDIT']] = 0; elseif ($type === 'income') $filter['>'.FIELDS['DEBIT']] = 0;
            if (!empty($_POST['filter_payer'])) $filter['%'.FIELDS['PAYER']] = trim($_POST['filter_payer']);
            if (!empty($_POST['filter_payee'])) $filter['%'.FIELDS['PAYEE']] = trim($_POST['filter_payee']);
            $search = trim($_POST['search'] ?? '');
            if ($search) {
                $subFilter = ['LOGIC' => 'OR', '%'.FIELDS['PAYER'] => $search, '%'.FIELDS['PAYEE'] => $search, '%'.FIELDS['PURPOSE'] => $search];
                if (is_numeric($search)) { $subFilter['='.FIELDS['DEBIT']] = $search; $subFilter['='.FIELDS['CREDIT']] = $search; $subFilter['='.FIELDS['DOC_NUM']] = $search; }
                $filter[] = $subFilter;
            }
            $expType = $_POST['filter_exp_type'] ?? '';
            if ($expType) {
                $filter[] = ['LOGIC' => 'OR', '='.FIELDS['EXPENSE_TYPE'] => $expType, '='.FIELDS['INCOME_TYPE'] => $expType];
            }
            $res = CRest::call('crm.item.list', ['entityTypeId' => SP_ID, 'order' => ['id' => 'DESC'], 'start' => $start, 'filter' => $filter, 'select' => ['*', 'TITLE']]);
            $items = $res['result']['items'] ?? []; $total = $res['total'] ?? 0;
            
            $getCount = function($extraFilter) use ($filter) { $f = array_merge($filter, $extraFilter); return CRest::call('crm.item.list', ['entityTypeId' => SP_ID, 'select' => ['ID'], 'limit' => 1, 'filter' => $f])['total'] ?? 0; };
            $cntRedExp = $getCount(['!='.FIELDS['IGNORE_FLAG'] => 'Да', '>'.FIELDS['CREDIT'] => 0, '>'.FIELDS['REMAINDER'] => 0.01]);
            $cntRedInc = $getCount(['!='.FIELDS['IGNORE_FLAG'] => 'Да', '>'.FIELDS['DEBIT'] => 0, '='.FIELDS['LINK_INCOME'] => false]);
            $statRed = $cntRedExp + $cntRedInc; $statGreen = $total - $statRed; if ($statGreen < 0) $statGreen = 0;

            $inns = []; foreach ($items as $item) { if (!empty($item[FIELDS['PAYER_INN']])) $inns[] = $item[FIELDS['PAYER_INN']]; if (!empty($item[FIELDS['PAYEE_INN']])) $inns[] = $item[FIELDS['PAYEE_INN']]; } $inns = array_unique($inns);
            $companyMap = []; if (!empty($inns)) { $batch = []; foreach ($inns as $inn) $batch['f_'.$inn] = ['method' => 'crm.company.list', 'params' => ['filter' => ['RQ_INN' => $inn], 'select' => ['ID', 'TITLE']]]; $bRes = CRest::callBatch($batch); foreach ($bRes['result']['result'] ?? [] as $k => $v) { if (!empty($v[0])) $companyMap[str_replace('f_', '', $k)] = ['id' => $v[0]['ID'], 'title' => $v[0]['TITLE']]; } }
            foreach ($items as &$item) {
                $inn1 = $item[FIELDS['PAYER_INN']]; if (isset($companyMap[$inn1])) $item['__PAYER_RESOLVED'] = $companyMap[$inn1]; $item['__PAYER_TEXT'] = $item[FIELDS['PAYER']] ?: ($item['title'] ?: '-');
                $inn2 = $item[FIELDS['PAYEE_INN']]; if (isset($companyMap[$inn2])) $item['__PAYEE_RESOLVED'] = $companyMap[$inn2]; $item['__PAYEE_TEXT'] = $item[FIELDS['PAYEE']] ?: ($item['title'] ?: '-');
            }
            echo json_encode(['items' => $items, 'total' => $total, 'page' => $page, 'stats' => ['total' => $total, 'green' => $statGreen, 'red' => $statRed]]); exit;
        }

        // 6. PROCESS REFUND
        if ($action === 'process_refund') {
            $targetId = (int)$_POST['target_id'];
            $paymentId = (int)$_POST['payment_id'];
            $targetType = $_POST['target_type']; 

            $payRes = CRest::call('crm.item.get', ['entityTypeId' => SP_ID, 'id' => $paymentId]);
            $payment = $payRes['result']['item'] ?? null;
            if (!$payment) throw new Exception('Платеж не найден');

            $amount = 0;
            $dateShort = date('d.m.Y', strtotime($payment[FIELDS['DATE']]));
            if ($targetType === 'expense') $amount = (float)$payment[FIELDS['DEBIT']];
            else $amount = (float)$payment[FIELDS['CREDIT']];

            if ($amount <= 0) throw new Exception('Сумма возврата в платеже равна 0');

            CRest::call('crm.item.update', ['entityTypeId' => SP_ID, 'id' => $paymentId, 'fields' => [FIELDS['IGNORE_FLAG'] => 'Да']]);

            $targetEntityId = ($targetType === 'expense') ? SP_EXPENSE_ID : SP_INCOME_ID;
            $linkField = ($targetType === 'expense') ? TARGET_EXPENSE_FIELDS['LINK_IMPORT'] : TARGET_INCOME_FIELDS['LINK_IMPORT'];
            $histField = ($targetType === 'expense') ? TARGET_EXPENSE_FIELDS['INFO_STR'] : TARGET_INCOME_FIELDS['INFO_STR'];

            $targetRes = CRest::call('crm.item.get', ['entityTypeId' => $targetEntityId, 'id' => $targetId, 'select' => ['ID', 'OPPORTUNITY', $linkField, $histField]]);
            $targetItem = $targetRes['result']['item'] ?? null;
            if (!$targetItem) throw new Exception('Целевой элемент не найден');

            $currentOpp = (float)$targetItem['opportunity'];
            $newOpp = $currentOpp - $amount;
            
            $rawLinks = $targetItem[$linkField] ?? [];
            $currentLinks = array_map('intval', is_array($rawLinks) ? $rawLinks : []);
            if (!in_array($paymentId, $currentLinks)) $currentLinks[] = $paymentId;
            $finalLinks = array_values(array_unique($currentLinks));

            $currentHist = (string)($targetItem[$histField] ?? '');
            $histToken = "-" . number_format($amount, 2, '.', '') . '/' . $dateShort;
            $newHist = $currentHist ? ($currentHist . '/' . $histToken) : $histToken;

            CRest::call('crm.item.update', [
                'entityTypeId' => $targetEntityId,
                'id' => $targetId,
                'fields' => [
                    'OPPORTUNITY' => $newOpp,
                    $linkField => $finalLinks,
                    $histField => $newHist
                ]
            ]);

            echo json_encode(['success' => true, 'new_amount' => $newOpp, 'new_links' => $finalLinks]); exit;
        }

        // 7. UPDATE FIELD
        if ($action === 'update_field') { $id = $_POST['id']; $field = $_POST['field']; $value = $_POST['value']; $updateData = []; if ($field === 'calc_type') $updateData[FIELDS['CALC_TYPE']] = $value; if ($field === 'ignore') $updateData[FIELDS['IGNORE_FLAG']] = ($value === 'true' ? 'Да' : 'Нет'); if ($field === 'expense_type') $updateData[FIELDS['EXPENSE_TYPE']] = $value; if ($field === 'income_type') $updateData[FIELDS['INCOME_TYPE']] = $value; if (!empty($updateData)) { $res = CRest::call('crm.item.update', ['entityTypeId' => SP_ID, 'id' => $id, 'fields' => $updateData]); echo json_encode($res); } exit; }
        
        if ($action === 'upload_csv') { if (!isset($_FILES['csv_file'])) { echo json_encode(['error'=>'Нет файла']); exit; } $content = file_get_contents($_FILES['csv_file']['tmp_name']); $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'CP1251'], true); if ($encoding && $encoding !== 'UTF-8') $content = mb_convert_encoding($content, 'UTF-8', $encoding); $rows = array_map(function($line){ return str_getcsv($line, ";"); }, explode("\n", $content)); $imported = 0; $duplicates = 0; foreach ($rows as $i => $data) { if ($i === 0 || count($data) < 10) continue; $docNum = $data[5] ?? ''; if (!$docNum) continue; $amount = (float)str_replace([' ', "\xC2\xA0"], '', $data[7] ?? '0'); $check = CRest::call('crm.item.list', ['entityTypeId' => SP_ID, 'filter' => ['='.FIELDS['DOC_NUM'] => $docNum], 'select' => ['ID', FIELDS['DEBIT'], FIELDS['CREDIT']]]); if(!empty($check['result']['items'])) { $duplicates++; continue; } $debit = 0; $credit = 0; if ($amount > 0) $debit = $amount; else $credit = abs($amount); $payer = $data[18]??''; $payee = $data[12]??''; $title = ($credit > 0 ? $payee : $payer) ?: "ПП $docNum"; $fields = ['TITLE' => $title, FIELDS['PAYER'] => $payer, FIELDS['PAYER_INN'] => preg_replace('/\D/', '', $data[19]??''), FIELDS['PAYEE'] => $payee, FIELDS['PAYEE_INN'] => preg_replace('/\D/', '', $data[13]??''), FIELDS['DEBIT'] => $debit, FIELDS['CREDIT'] => $credit, FIELDS['PURPOSE'] => $data[9]??'', FIELDS['DATE'] => $data[4]??'', FIELDS['DOC_NUM'] => $docNum, FIELDS['CALC_TYPE'] => 'ВТБ Банк', FIELDS['REMAINDER'] => ($credit > 0 ? $credit : 0), FIELDS['TOTAL_ALLOC'] => 0]; CRest::call('crm.item.add', ['entityTypeId' => SP_ID, 'fields' => $fields]); $imported++; } echo json_encode(['imported'=>$imported, 'duplicates'=>$duplicates]); exit; }
        if ($action === 'create_company') { $res = CRest::call('crm.company.add', ['fields' => ['TITLE' => $_POST['title']]]); if (isset($res['result'])) { $preset = CRest::call('crm.requisite.preset.list', ['filter' => ['COUNTRY_ID' => 1]]); CRest::call('crm.requisite.add', ['fields' => ['ENTITY_TYPE_ID' => 4, 'ENTITY_ID' => $res['result'], 'PRESET_ID' => $preset['result'][0]['ID'] ?? 1, 'NAME' => 'Организация', 'RQ_INN' => $_POST['inn']]]); } echo json_encode($res); exit; }
        if ($action === 'delete_item') { $res = CRest::call('crm.item.delete', ['entityTypeId' => SP_ID, 'id' => $_POST['id']]); echo json_encode($res); exit; }
        
        // 8. SAVE ITEM
        if ($action === 'save_item') { 
            $debug = [];
            $id = (int)($_POST['id'] ?? 0); $fields = []; 
            $fields[FIELDS['DATE']] = $_POST['date']; $fields[FIELDS['DOC_NUM']] = $_POST['doc_num']; $fields[FIELDS['PAYER']] = $_POST['payer']; $fields[FIELDS['PAYER_INN']] = $_POST['payer_inn']; $fields[FIELDS['PAYEE']] = $_POST['payee']; $fields[FIELDS['PAYEE_INN']] = $_POST['payee_inn']; $fields[FIELDS['PURPOSE']] = $_POST['purpose']; $fields[FIELDS['CALC_TYPE']] = $_POST['calc_type']; $fields[FIELDS['COMMENT']] = $_POST['comment']; $fields[FIELDS['EXPENSE_TYPE']] = $_POST['expense_type']; $fields[FIELDS['INCOME_TYPE']] = $_POST['income_type']; 
            $amount = (float)$_POST['amount']; $type = $_POST['op_type']; 
            if ($type === 'expense') { $fields[FIELDS['DEBIT']] = 0; $fields[FIELDS['CREDIT']] = $amount; } else { $fields[FIELDS['DEBIT']] = $amount; $fields[FIELDS['CREDIT']] = 0; } 
            
            $oldLinkedExpenses = []; $oldLinkedIncomes = [];
            $targetItemId = 0;

            $fields[FIELDS['JSON_SPLIT']] = $_POST['json_data']; 
            $fields[FIELDS['SPLIT_READABLE']] = $_POST['split_readable']; 
            $fields[FIELDS['TOTAL_ALLOC']] = $_POST['total_alloc']; 
            $fields[FIELDS['SUM_EXPENSE']] = $_POST['total_alloc']; 
            $rem = (float)$_POST['remainder']; if (abs($rem) < 0.01) $rem = 0; $fields[FIELDS['REMAINDER']] = $rem;
            $fields[FIELDS['IGNORE_FLAG']] = ($_POST['ignore'] === 'true' ? 'Да' : 'Нет'); 
            
            $newLinkedExpenses = []; $newLinkedIncomes = [];
            $linkedData = []; 

            if ($type === 'expense') {
                $linkedData = json_decode($_POST['json_data'], true) ?? [];
                foreach ($linkedData as $row) { if($row['id']) $newLinkedExpenses[] = (int)$row['id']; }
                $fields[FIELDS['LINK_EXPENSE']] = $newLinkedExpenses;
                $fields[FIELDS['LINK_INCOME']] = [];
            } else {
                $newLinkedIncomes = $_POST['linked_income'] ?? [];
                if (!is_array($newLinkedIncomes)) $newLinkedIncomes = [];
                $newLinkedIncomes = array_map('intval', $newLinkedIncomes);
                $fields[FIELDS['LINK_INCOME']] = $newLinkedIncomes;
                $fields[FIELDS['LINK_EXPENSE']] = [];
            }

            if ($id > 0) {
                $oldItemRes = CRest::call('crm.item.get', ['entityTypeId' => SP_ID, 'id' => $id]);
                $oldItem = $oldItemRes['result']['item'] ?? [];
                $oldLinkedExpenses = array_map('intval', $oldItem[FIELDS['LINK_EXPENSE']] ?? []);
                $oldLinkedIncomes = array_map('intval', $oldItem[FIELDS['LINK_INCOME']] ?? []);
                $res = CRest::call('crm.item.update', ['entityTypeId' => SP_ID, 'id' => $id, 'fields' => $fields]); 
                $targetItemId = $id;
            } else {
                if ($type === 'expense') $fields[FIELDS['REMAINDER']] = $amount; 
                $fields['TITLE'] = ($type === 'expense' ? $_POST['payee'] : $_POST['payer']) ?: 'Новый платеж'; 
                $res = CRest::call('crm.item.add', ['entityTypeId' => SP_ID, 'fields' => $fields]); 
                $targetItemId = $res['result']['item']['id'] ?? $res['result']['id'];
            }

            if ($targetItemId > 0) {
                $dateShort = date('d.m.Y', strtotime($fields[FIELDS['DATE']]));
                $targetInt = (int)$targetItemId;
                $batch = [];
                
                $allExp = array_unique(array_merge($oldLinkedExpenses, $newLinkedExpenses));
                $allInc = array_unique(array_merge($oldLinkedIncomes, $newLinkedIncomes));

                // 1. EXPENSES
                foreach ($allExp as $expId) {
                    $isLinked = in_array($expId, $newLinkedExpenses);
                    // FETCH CURRENT with explicit fields
                    $cur = CRest::call('crm.item.get', ['entityTypeId' => SP_EXPENSE_ID, 'id' => $expId, 'select' => ['ID', TARGET_EXPENSE_FIELDS['LINK_IMPORT'], TARGET_EXPENSE_FIELDS['INFO_STR']]]);
                    $rawLinks = $cur['result']['item'][TARGET_EXPENSE_FIELDS['LINK_IMPORT']] ?? [];
                    $curLinks = array_map('intval', is_array($rawLinks) ? $rawLinks : []);
                    $curHist = (string)($cur['result']['item'][TARGET_EXPENSE_FIELDS['INFO_STR']] ?? '');
                    
                    if ($isLinked) {
                        // ADD
                        if (!in_array($targetInt, $curLinks)) $curLinks[] = $targetInt;
                        $finalLinks = array_values(array_unique($curLinks));
                        
                        // Append History
                        $alloc = 0;
                        if ($type === 'expense') foreach ($linkedData as $row) if ((int)$row['id'] === $expId) $alloc = (float)$row['amount'];
                        $token = number_format($alloc, 2, '.', '') . '/' . $dateShort;
                        
                        $newHist = $curHist;
                        if (strpos($curHist, $token) === false) {
                            $newHist = $curHist ? ($curHist . '/' . $token) : $token;
                        }

                        $batch['exp_upd_'.$expId] = ['method' => 'crm.item.update', 'params' => ['entityTypeId' => SP_EXPENSE_ID, 'id' => $expId, 'fields' => [ TARGET_EXPENSE_FIELDS['LINK_IMPORT'] => $finalLinks, TARGET_EXPENSE_FIELDS['INFO_STR'] => $newHist ]]];
                    } else {
                        // REMOVE
                        $curLinks = array_diff($curLinks, [$targetInt]);
                        $finalLinks = array_values($curLinks);
                        if(empty($finalLinks)) $finalLinks = "";
                        
                        $batch['exp_rem_'.$expId] = ['method' => 'crm.item.update', 'params' => ['entityTypeId' => SP_EXPENSE_ID, 'id' => $expId, 'fields' => [ TARGET_EXPENSE_FIELDS['LINK_IMPORT'] => $finalLinks ]]];
                    }
                }

                // 2. INCOMES
                foreach ($allInc as $incId) {
                    $isLinked = in_array($incId, $newLinkedIncomes);
                    $cur = CRest::call('crm.item.get', ['entityTypeId' => SP_INCOME_ID, 'id' => $incId, 'select' => ['ID', TARGET_INCOME_FIELDS['LINK_IMPORT'], TARGET_INCOME_FIELDS['INFO_STR']]]);
                    $rawLinks = $cur['result']['item'][TARGET_INCOME_FIELDS['LINK_IMPORT']] ?? [];
                    $curLinks = array_map('intval', is_array($rawLinks) ? $rawLinks : []);
                    $curHist = (string)($cur['result']['item'][TARGET_INCOME_FIELDS['INFO_STR']] ?? '');

                    if ($isLinked) {
                        if (!in_array($targetInt, $curLinks)) $curLinks[] = $targetInt;
                        $finalLinks = array_values(array_unique($curLinks));
                        
                        $token = number_format($amount, 2, '.', '') . '/' . $dateShort;
                        $newHist = $curHist;
                        if (strpos($curHist, $token) === false) {
                            $newHist = $curHist ? ($curHist . '/' . $token) : $token;
                        }
                        
                        $batch['inc_upd_'.$incId] = ['method' => 'crm.item.update', 'params' => ['entityTypeId' => SP_INCOME_ID, 'id' => $incId, 'fields' => [ TARGET_INCOME_FIELDS['LINK_IMPORT'] => $finalLinks, TARGET_INCOME_FIELDS['INFO_STR'] => $newHist ]]];
                    } else {
                        $curLinks = array_diff($curLinks, [$targetInt]);
                        $finalLinks = array_values($curLinks);
                        if(empty($finalLinks)) $finalLinks = "";
                        
                        // Attempt removal of token
                        $token = number_format($amount, 2, '.', '') . '/' . $dateShort;
                        $parts = explode('/', $curHist);
                        $newParts = [];
                        for($i=0; $i<count($parts); $i+=2) {
                            $pAmt = $parts[$i] ?? ''; $pDate = $parts[$i+1] ?? '';
                            $curToken = $pAmt.'/'.$pDate;
                            if ($curToken === $token) continue; // Remove
                            if($pAmt && $pDate) { $newParts[] = $pAmt; $newParts[] = $pDate; }
                        }
                        $newHist = implode('/', $newParts);

                        $batch['inc_rem_'.$incId] = ['method' => 'crm.item.update', 'params' => ['entityTypeId' => SP_INCOME_ID, 'id' => $incId, 'fields' => [ TARGET_INCOME_FIELDS['LINK_IMPORT'] => $finalLinks, TARGET_INCOME_FIELDS['INFO_STR'] => $newHist ]]];
                    }
                }

                if (!empty($batch)) {
                    $debug['batch'] = CRest::callBatch($batch);
                }
            }
            echo json_encode(['result' => $targetItemId, 'debug' => $debug]); exit; 
        }
        
        if ($action === 'bulk_update') { $ids = $_POST['ids'] ?? []; $field = $_POST['field']; $value = $_POST['value']; $updateData = []; if ($field === 'ignore') $updateData[FIELDS['IGNORE_FLAG']] = ($value === 'true' ? 'Да' : 'Нет'); if ($field === 'expense_type') $updateData[FIELDS['EXPENSE_TYPE']] = $value; if ($field === 'income_type') $updateData[FIELDS['INCOME_TYPE']] = $value; $batch = []; foreach ($ids as $id) { $batch['upd_'.$id] = ['method' => 'crm.item.update', 'params' => ['entityTypeId' => SP_ID, 'id' => $id, 'fields' => $updateData]]; } if(!empty($batch)) { foreach(array_chunk($batch, 50) as $chunk) CRest::callBatch($chunk); } echo json_encode(['success' => true]); exit; }
        if ($action === 'bulk_create_sp') { $ids = $_POST['ids'] ?? []; $type = $_POST['target_type']; $results = []; foreach ($ids as $id) { try { if($type == 'expense') performCreateSP($id, 'expense'); else performCreateSP($id, 'income'); $results[$id] = 'OK'; } catch (Exception $e) { $results[$id] = $e->getMessage(); } } echo json_encode(['success' => true]); exit; }

    } catch (Throwable $e) { ob_end_clean(); echo json_encode(['error' => true, 'msg' => $e->getMessage()]); exit; }
}

function performCreateSP($sourceId, $mode) {
    $sourceId = (int)$sourceId;
    $source = CRest::call('crm.item.get', ['entityTypeId' => SP_ID, 'id' => $sourceId]);
    $item = $source['result']['item'] ?? null;
    if (!$item) throw new Exception('Платеж не найден');

    $date = $item[FIELDS['DATE']];
    $dateShort = date('d.m.Y', strtotime($date));
    
    $payerInn = trim((string)$item[FIELDS['PAYER_INN']]);
    $payeeInn = trim((string)$item[FIELDS['PAYEE_INN']]);
    $payerName = trim((string)$item[FIELDS['PAYER']]);
    $payeeName = trim((string)$item[FIELDS['PAYEE']]);
    
    $findCompany = function($inn, $name) { 
        if ($inn) { $resReq = CRest::call('crm.requisite.list', ['filter' => ['RQ_INN' => $inn, 'ENTITY_TYPE_ID' => 4], 'select' => ['ENTITY_ID']]); if (!empty($resReq['result'][0]['ENTITY_ID'])) return $resReq['result'][0]['ENTITY_ID']; } 
        if ($name) { $resCo = CRest::call('crm.company.list', ['filter' => ['=TITLE' => $name], 'select' => ['ID']]); if (!empty($resCo['result'][0]['ID'])) return $resCo['result'][0]['ID']; } 
        return null; 
    };
    $payerId = $findCompany($payerInn, $payerName);
    $payeeId = $findCompany($payeeInn, $payeeName);

    if ($mode === 'expense') {
        $credit = (float)$item[FIELDS['CREDIT']];
        $expenseType = $item[FIELDS['EXPENSE_TYPE']];
        
        $fields = [ 'TITLE' => "Расход {$credit} от {$dateShort}", 'OPPORTUNITY' => $credit, 'STAGE_ID' => TARGET_EXPENSE_FIELDS['STAGE'], TARGET_EXPENSE_FIELDS['DATE'] => $date ];
        if ($expenseType) $fields[TARGET_EXPENSE_FIELDS['TYPE']] = $expenseType;
        if ($payeeId) $fields[TARGET_EXPENSE_FIELDS['PAYEE']] = "CO_" . $payeeId;
        if ($payerId) { $fields[TARGET_EXPENSE_FIELDS['PAYER_1']] = "CO_" . $payerId; $fields[TARGET_EXPENSE_FIELDS['PAYER_2']] = "CO_" . $payerId; }

        $resAdd = CRest::call('crm.item.add', ['entityTypeId' => SP_EXPENSE_ID, 'fields' => $fields]);
        if (isset($resAdd['error'])) throw new Exception('Add Expense: ' . $resAdd['error_description']);
        $newSpId = $resAdd['result']['item']['id'] ?? $resAdd['result']['id'];
        
        $hist = number_format($credit, 2, '.', '') . '/' . $dateShort;
        CRest::call('crm.item.update', ['entityTypeId' => SP_EXPENSE_ID, 'id' => $newSpId, 'fields' => [ TARGET_EXPENSE_FIELDS['LINK_IMPORT'] => [(int)$sourceId], TARGET_EXPENSE_FIELDS['INFO_STR'] => $hist ]]);
        
        $jsonSplit = json_encode([['id' => $newSpId, 'amount' => $credit, 'category' => $expenseType]]);
        $readable = "АВТО: ID {$newSpId} ({$credit})";
        $currentLinks = $item[FIELDS['LINK_EXPENSE']] ?? []; if (!is_array($currentLinks)) $currentLinks = []; $currentLinks[] = $newSpId;
        CRest::call('crm.item.update', [ 'entityTypeId' => SP_ID, 'id' => $sourceId, 'fields' => [ FIELDS['LINK_EXPENSE'] => $currentLinks, FIELDS['JSON_SPLIT'] => $jsonSplit, FIELDS['SPLIT_READABLE'] => $readable, FIELDS['TOTAL_ALLOC'] => $credit, FIELDS['SUM_EXPENSE'] => $credit, FIELDS['REMAINDER'] => 0 ] ]);

    } else {
        $debit = (float)$item[FIELDS['DEBIT']];
        $incomeType = $item[FIELDS['INCOME_TYPE']];
        
        $fields = [ 'TITLE' => "Поступление {$debit} от {$dateShort}", 'OPPORTUNITY' => $debit, 'STAGE_ID' => TARGET_INCOME_FIELDS['STAGE'], TARGET_INCOME_FIELDS['DATE'] => $date ];
        if ($incomeType) $fields[TARGET_INCOME_FIELDS['TYPE']] = $incomeType;
        if ($payerId) $fields[TARGET_INCOME_FIELDS['PAYER']] = "CO_" . $payerId;
        if ($payeeId) { $fields[TARGET_INCOME_FIELDS['PAYEE_1']] = "CO_" . $payeeId; $fields[TARGET_INCOME_FIELDS['PAYEE_2']] = "CO_" . $payeeId; }
        
        $resAdd = CRest::call('crm.item.add', ['entityTypeId' => SP_INCOME_ID, 'fields' => $fields]);
        if (isset($resAdd['error'])) throw new Exception('Add Income: ' . $resAdd['error_description']);
        $newSpId = $resAdd['result']['item']['id'] ?? $resAdd['result']['id'];
        
        $hist = number_format($debit, 2, '.', '') . '/' . $dateShort;
        CRest::call('crm.item.update', ['entityTypeId' => SP_INCOME_ID, 'id' => $newSpId, 'fields' => [ TARGET_INCOME_FIELDS['LINK_IMPORT'] => [(int)$sourceId], TARGET_INCOME_FIELDS['INFO_STR'] => $hist ]]);

        $currentLinks = $item[FIELDS['LINK_INCOME']] ?? []; if (!is_array($currentLinks)) $currentLinks = []; $currentLinks[] = $newSpId;
        CRest::call('crm.item.update', ['entityTypeId' => SP_ID, 'id' => $sourceId, 'fields' => [FIELDS['LINK_INCOME'] => $currentLinks]]);
    }
    return $newSpId;
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Казначейство</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <style>
        body { background: #f8f9fa; font-size: 0.8rem; padding: 10px; }
        .table-container { overflow-x: auto; }
        table { min-width: 1200px; width: 100%; }
        
        /* CSS Fix for Modal Table */
        #selResults table { width: 100% !important; table-layout: fixed; min-width: auto !important; }
        #selResults td, #selResults th { 
            white-space: normal !important; 
            word-wrap: break-word; 
            font-size: 0.75rem; 
        }
        #selResults th:nth-child(1) { width: 10%; } /* ID */
        #selResults th:nth-child(2) { width: 40%; } /* Name */
        #selResults th:nth-child(3) { width: 30%; text-align: right; } /* Amount/Date */
        #selResults th:nth-child(4) { width: 20%; text-align: center; } /* Btn */

        /* Standard CSS */
        .col-id { width: 60px; text-align: center; color: #999; }
        .col-date { width: 110px; white-space: nowrap; vertical-align: top; }
        .col-company { width: 18%; min-width: 180px; }
        .col-purpose { width: 30%; min-width: 250px; }
        .col-sum { width: 120px; white-space: nowrap; text-align: right; font-weight: bold; font-family: monospace; }
        .col-calc { width: 110px; }
        .col-type { width: 130px; }
        .col-status { width: 130px; }
        .col-action { width: 110px; white-space: nowrap; text-align: right; }
        
        .row-green { background-color: #d1e7dd !important; }
        .row-red { background-color: #f8d7da !important; }
        .text-green { color: #0f5132; font-weight: bold; }
        .text-red { color: #842029; font-weight: bold; }
        .company-link { color: #0d6efd; cursor: pointer; text-decoration: none; border-bottom: 1px dotted #0d6efd; word-wrap: break-word; }
        .company-link:hover { border-bottom: 1px solid #0d6efd; }
        .text-purpose { font-size: 0.75rem; line-height: 1.3; max-height: 50px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; white-space: normal; }
        .table-input { font-size: 0.8rem; padding: 2px 5px; height: 28px; background-color: transparent; border: 1px solid transparent; width: 100%; cursor: pointer; }
        .table-input:hover, .table-input:focus { background-color: #fff; border: 1px solid #ced4da; outline: none; }
        .saving-field { background-color: #fff3cd !important; } 
        .saved-field { background-color: #d1e7dd !important; transition: background 1s; }
        .loader { position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:9999;display:none;justify-content:center;align-items:center;}
        .modal { z-index: 1055 !important; }
        .modal-backdrop { z-index: 1050 !important; }
        .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
        .stats-bar { display: flex; gap: 10px; margin-bottom: 10px; font-size: 0.9rem; flex-wrap: wrap; }
        .stat-item { padding: 5px 12px; border-radius: 20px; background: #fff; border: 1px solid #ddd; font-weight: bold; }
        .stat-total { color: #0d6efd; border-color: #0d6efd; }
        .stat-green { color: #198754; border-color: #198754; background-color: #e8f5e9; }
        .stat-red { color: #dc3545; border-color: #dc3545; background-color: #f8d7da; }
        #bulkToolbar { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #343a40; color: #fff; padding: 10px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: none; z-index: 1050; align-items: center; gap: 15px; }
        
        /* Mobile: Smaller fonts in Modal */
        @media (max-width: 768px) {
             #selResults td, #selResults th { font-size: 0.7rem; padding: 4px; }
             #selResults .btn-primary { padding: 2px 6px; font-size: 0.7rem; }
        }
    </style>
</head>
<body>

<!-- LOADER, BULK, CONTAINER, PANEL, MODALS (Same as v86) -->
<div class="loader" id="loader"><div class="spinner-border text-primary"></div></div>

<div id="bulkToolbar"><span id="bulkCount" class="fw-bold">0 выбрано</span><div class="vr bg-secondary"></div><select class="form-select form-select-sm bg-dark text-light border-secondary" style="width:150px" id="bulkCat"><option value="">Категория...</option></select><button class="btn btn-sm btn-light" onclick="bulkUpdate('cat')">OK</button><div class="vr bg-secondary"></div><button class="btn btn-sm btn-outline-light" onclick="bulkUpdate('ignore', true)">Не учит.</button><button class="btn btn-sm btn-outline-light" onclick="bulkUpdate('ignore', false)">Учит.</button><div class="vr bg-secondary"></div><button class="btn btn-sm btn-success" onclick="bulkCreateSP()">Создать +СП</button><button class="btn btn-sm btn-close btn-close-white ms-2" onclick="deselectAll()"></button></div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">🏦 Казначейство</h5><div><button class="btn btn-success btn-sm me-1" onclick="openImport()">📥 Импорт CSV</button><button class="btn btn-primary btn-sm" onclick="openEdit(0)">➕ Создать</button></div></div>
    
    <div class="stats-bar"><div class="stat-item stat-total">Всего: <span id="cntTotal">0</span></div><div class="stat-item stat-green">✅ Закрыто: <span id="cntGreen">0</span></div><div class="stat-item stat-red">🔴 В работе: <span id="cntRed">0</span></div></div>
    
    <div class="card p-2 mb-2 shadow-sm"><div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Умный поиск</label><input type="text" id="search" class="form-control form-control-sm" placeholder="Назначение, Сумма..." onkeyup="if(event.keyCode===13) loadGrid(1)"></div><div class="col-md-3"><label class="form-label">Плательщик</label><input type="text" id="fltPayer" class="form-control form-control-sm" placeholder="Часть названия..." onkeyup="if(event.keyCode===13) loadGrid(1)"></div><div class="col-md-3"><label class="form-label">Получатель</label><input type="text" id="fltPayee" class="form-control form-control-sm" placeholder="Часть названия..." onkeyup="if(event.keyCode===13) loadGrid(1)"></div><div class="col-md-3"><button class="btn btn-outline-secondary btn-sm w-100 mt-4" onclick="loadGrid(1)">🔍 Найти</button></div></div></div>
    
    <div class="card p-2 mb-3 shadow-sm bg-light border-0"><div class="row g-2 align-items-end"><div class="col-md-2"><label class="form-label">Тип</label><select id="fltType" class="form-select form-select-sm" onchange="loadGrid(1)"><option value="">Все</option><option value="expense">Расход</option><option value="income">Поступление</option></select></div><div class="col-md-2"><label class="form-label">Категория</label><select id="fltExpType" class="form-select form-select-sm" onchange="loadGrid(1)"><option value="">Все</option></select></div><div class="col-md-2"><label class="form-label">Статус</label><select id="fltStatus" class="form-select form-select-sm" onchange="loadGrid(1)"><option value="">Все</option><option value="ok">OK</option><option value="alert">Внимание</option></select></div></div></div>

    <div class="bg-white border rounded shadow-sm table-container">
        <table class="table table-hover align-middle mb-0 table-sm">
            <thead class="table-light text-secondary">
                <tr>
                    <th width="30"><input type="checkbox" id="checkAll" onchange="toggleAll(this)"></th>
                    <th class="col-id">ID</th><th class="col-date">Дата</th><th class="col-company">Плательщик</th><th class="col-company">Получатель</th><th class="col-purpose">Назначение</th><th class="col-sum">Сумма</th><th class="col-calc">Расчет</th><th class="col-type">Категория</th><th class="col-status">Статус</th><th class="col-action"></th>
                </tr>
            </thead>
            <tbody id="gridBody"></tbody>
        </table>
    </div>
    
    <div class="pagination-container">
        <button class="btn btn-outline-secondary btn-sm" id="btnPrev" onclick="changePage(-1)" disabled>← Назад</button>
        <span class="text-muted small">Страница: <span id="pageNum">1</span></span>
        <button class="btn btn-outline-secondary btn-sm" id="btnNext" onclick="changePage(1)">Вперед →</button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panel" style="width: 600px;"><div class="offcanvas-header bg-light py-2"><h5 class="offcanvas-title" id="panelTitle">Редактирование</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body bg-light"><form id="editForm"><input type="hidden" id="editId"><div class="panel-section"><h6>Данные платежа</h6><div class="row g-2 mb-2"><div class="col-4"><label class="form-label">Дата</label><input type="date" class="form-control form-control-sm" id="inpDate" required></div><div class="col-4"><label class="form-label">Номер ПП</label><input type="text" class="form-control form-control-sm" id="inpNum"></div><div class="col-4"><label class="form-label">Тип расчета</label><select class="form-select form-select-sm" id="inpCalcType"><option value="ВТБ Банк">ВТБ Банк</option><option value="Материальный расчет">Материальный расчет</option><option value="Иной">Иной</option></select></div></div><div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Плательщик</label><input type="text" class="form-control form-control-sm mb-1" id="inpPayer"><input type="text" class="form-control form-control-sm" id="inpPayerInn" placeholder="ИНН"></div><div class="col-6"><label class="form-label">Получатель</label><input type="text" class="form-control form-control-sm mb-1" id="inpPayee"><input type="text" class="form-control form-control-sm" id="inpPayeeInn" placeholder="ИНН"></div></div><div class="row g-2 mb-2"><div class="col-6"><label class="form-label">Сумма</label><input type="number" step="0.1" class="form-control form-control-sm fw-bold" id="inpAmount" oninput="recalcPanel()"></div><div class="col-6"><label class="form-label">Операция</label><select class="form-select form-select-sm" id="inpOpType" onchange="toggleDistMode()"><option value="expense">Расход</option><option value="income">Поступление</option></select></div></div><div class="mb-2"><label class="form-label">Назначение</label><textarea class="form-control form-control-sm" id="inpPurpose" rows="3"></textarea></div><div class="mb-0" id="blockExpCat"><label class="form-label">Категория Расхода</label><select class="form-select form-select-sm" id="inpExpenseType"><option value="">Не выбрано</option></select></div><div class="mb-0" id="blockIncCat" style="display:none"><label class="form-label">Категория Дохода</label><select class="form-select form-select-sm" id="inpIncomeType"><option value="">Не выбрано</option></select></div></div><div class="panel-section" id="blockExpense"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Распределение расхода</h6><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="inpIgnore"><label class="form-check-label small" for="inpIgnore">Не учитывать</label></div></div><div class="row g-1 mb-1 fw-bold small text-muted"><div class="col-5">Связанный элемент</div><div class="col-4">Категория</div><div class="col-2">Сумма</div></div><div id="distRows"></div><button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" onclick="addDistRow()">+ Добавить</button><button type="button" class="btn btn-outline-danger btn-sm mt-2 w-100" onclick="addRefund()">↩️ Добавить возврат</button><div class="mt-3 border-top pt-2"><div class="d-flex justify-content-between text-success"><span>Распределено:</span> <span id="lblDist">0.00</span></div><div class="d-flex justify-content-between text-danger fw-bold"><span>Остаток:</span> <span id="lblRem">0.00</span></div></div></div><div class="panel-section" id="blockIncome" style="display:none;"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Привязка к планам</h6><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="inpIgnoreInc"><label class="form-check-label small" for="inpIgnoreInc">Не учитывать</label></div></div><div id="incomeLinks" class="mb-2"></div><button type="button" class="btn btn-outline-success btn-sm w-100" onclick="triggerIncomeSelector()">Выбрать элемент СП 1310</button><button type="button" class="btn btn-outline-danger btn-sm mt-2 w-100" onclick="addRefund()">↩️ Добавить возврат</button></div><div class="mb-3"><label class="form-label">Комментарий</label><input type="text" class="form-control form-control-sm" id="inpComment"></div><button type="button" class="btn btn-primary w-100 py-2" onclick="saveItem()">Сохранить</button></form></div></div>

<div class="modal fade" id="importModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Импорт</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="importForm"><input type="file" name="csv_file" class="form-control" accept=".csv"><input type="hidden" name="action" value="upload_csv"></form></div><div class="modal-footer"><button class="btn btn-primary" onclick="runImport()">Загрузить</button></div></div></div></div>

<div class="modal fade" id="selectorModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header bg-light py-2"><h5 class="modal-title" id="selTitle">Выбор</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="input-group mb-3"><input type="text" class="form-control" id="selSearch" placeholder="Поиск..." oninput="loadSelectorData()"><button class="btn btn-outline-primary" type="button" onclick="loadSelectorData()">Найти</button></div><div id="selResults"></div></div></div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const FIELDS = <?php echo json_encode(FIELDS); ?>;
    const SP_ID = <?php echo SP_ID; ?>;
    const SP_EXP = <?php echo SP_EXPENSE_ID; ?>;
    const SP_INC = <?php echo SP_INCOME_ID; ?>;
    let currentItems = []; let expenseTypes = []; let incomeTypes = []; let portalDomain = ''; let currentPage = 1;
    let selEntity = 0; let selCallback = null; let selModal = null; let selectorAllItems = [];
    let currentIncomeLinks = []; let editOffcanvas = null;

    function showLoader() { $('#loader').css('display','flex'); }
    function hideLoader() { $('#loader').hide(); }
    function formatDate(str) { if(!str) return ''; return new Date(str).toLocaleDateString(); }
    function renderResolvedCompany(item, keyRes, keyText, inn) { if (item[keyRes]) { let comp = item[keyRes]; let url = `https://${portalDomain}/crm/company/details/${comp.id}/`; return `<a href="${url}" target="_blank" class="company-link fw-bold">${item[keyText]}</a><div class="small text-muted">${inn||''}</div>`; } else { let text = item[keyText] || '-'; let btn = inn ? `<span class="text-primary cursor-pointer small ms-1" onclick="createCo('${inn}', '${text}')">[+]</span>` : ''; return `<div>${text}</div><div class="small text-muted">${inn||''} ${btn}</div>`; } }
    function batchCheckCompanies() { let requestMap = {}; $('.company-wrapper').each(function() { let el = $(this); let inn = el.data('inn'); let uid = el.attr('id'); if(inn && uid) { if(!requestMap[inn]) requestMap[inn] = []; requestMap[inn].push(uid); } }); let inns = Object.keys(requestMap); if(inns.length === 0) return; let batch = {}; inns.forEach(inn => { batch['req_' + inn] = { method: 'crm.company.list', params: { filter: { 'RQ_INN': inn }, select: ['ID', 'TITLE'] } }; }); BX24.callBatch(batch, function(result) { let innMap = {}; for (let reqKey in result) { if (!result.hasOwnProperty(reqKey)) continue; let res = result[reqKey]; if (res && !res.error() && res.data().length > 0) { let inn = reqKey.replace('req_', ''); innMap[inn] = { id: res.data()[0].ID, title: res.data()[0].TITLE }; } } $('.company-wrapper').each(function() { let el = $(this); let inn = el.data('inn'); let linkSpan = el.find('.company-link'); let originalName = el.data('name'); if(!inn) return; if (innMap[inn]) { let comp = innMap[inn]; let domain = portalDomain || window.location.hostname; let url = `https://${domain}/crm/company/details/${comp.id}/`; linkSpan.replaceWith(`<a href="${url}" target="_blank" class="company-link fw-bold">${originalName}</a>`); } else { if (linkSpan.hasClass('company-link-pending')) { linkSpan.removeClass('company-link company-link-pending').addClass('text-danger'); linkSpan.after(` <span class="text-primary cursor-pointer small" onclick="createCo('${inn}', '${originalName}')">[+]</span>`); } } }); }); }

    $(document).ready(function() {
        BX24.init(function(){
            portalDomain = BX24.getAuth().domain;
            loadDictionaries(); 
        });
    });

    function openCustomSelector(entityTypeId, cb, filterMode) { 
        selEntity = entityTypeId; 
        selCallback = cb; 
        $('#selSearch').val(''); 
        let title = 'Выбор ';
        if (entityTypeId == SP_ID) title += 'Платежа';
        else title += (entityTypeId == SP_EXP ? 'Заявки на расход' : 'Плана поступления');
        
        $('#selTitle').text(title); 
        
        if(!selModal) selModal = new bootstrap.Modal(document.getElementById('selectorModal')); 
        selModal.show(); 
        
        let auth = BX24.getAuth(); 
        $.post('index.php', { 
            action: 'load_selector', 
            auth: {access_token: auth.access_token, domain: auth.domain}, 
            entityTypeId: selEntity,
            filter_mode: filterMode 
        }, function(res) { 
            selectorAllItems = res.items || []; 
            loadSelectorData(); 
        }, 'json'); 
    }
    function loadSelectorData() { let search = $('#selSearch').val().toLowerCase(); let filtered = selectorAllItems.filter(i => { if(!search) return true; return i.TITLE.toLowerCase().includes(search) || i.ID == search || i.OPPORTUNITY == search; }); renderSelectorItems(filtered.slice(0, 100)); }
    
    // FIX: Render with Date below Amount
    function renderSelectorItems(items) { 
        if(items.length === 0) { $('#selResults').html('<div class="alert alert-warning">Ничего не найдено</div>'); return; } 
        let html = '<table class="table table-sm table-hover small table-striped mb-0"><thead><tr><th class="text-center">ID</th><th>Название</th><th class="text-end">Сумма / Дата</th><th class="text-center"></th></tr></thead><tbody>'; 
        items.forEach((i) => { 
            let amt = parseFloat(i.OPPORTUNITY || 0).toLocaleString(undefined, {minimumFractionDigits: 2}); 
            let date = i.DATE ? new Date(i.DATE).toLocaleDateString() : '-';
            html += `<tr>
                <td class="text-center text-muted">${i.ID}</td>
                <td class="fw-bold">${i.TITLE}</td>
                <td class="text-end">${amt}<br><small class="text-muted">${date}</small></td>
                <td class="text-center"><button class="btn btn-sm btn-primary" onclick="selectItem('${i.ID}', '${i.TITLE.replace(/'/g,"")}')">Выбрать</button></td>
            </tr>`; 
        }); 
        html += '</tbody></table>'; 
        $('#selResults').html(html); 
    }

    function selectItem(id, title) { if(selCallback) selCallback({id: id, title: title}); selModal.hide(); }
    
    function toggleAll(cb) { $('.row-checkbox').prop('checked', cb.checked); updateToolbar(); }
    function updateToolbar() { let cnt = $('.row-checkbox:checked').length; if(cnt > 0) { $('#bulkToolbar').css('display', 'flex'); $('#bulkCount').text(cnt + ' выбрано'); } else { $('#bulkToolbar').hide(); } }
    function deselectAll() { $('.row-checkbox').prop('checked', false); $('#checkAll').prop('checked', false); updateToolbar(); }
    function getSelectedIds() { let ids = []; $('.row-checkbox:checked').each(function() { ids.push($(this).val()); }); return ids; }
    
    function updateBulkCategorySelect() {
        let type = $('#fltType').val();
        let select = $('#bulkCat');
        select.empty().append('<option value="">Категория...</option>');
        if (type === 'expense') { expenseTypes.forEach(et => { select.append(`<option value="${et.ID}">${et.NAME}</option>`); }); } 
        else if (type === 'income') { incomeTypes.forEach(it => { select.append(`<option value="${it.ID}">${it.NAME}</option>`); }); }
    }

    function bulkUpdate(field, val) { 
        let ids = getSelectedIds(); 
        if(ids.length === 0) return; 
        if(field === 'cat') {
            let type = $('#fltType').val();
            if (!type) return alert('Сначала отфильтруйте список по Типу!');
            val = $('#bulkCat').val();
            if (!val) return alert('Выберите категорию');
            field = (type === 'expense') ? 'expense_type' : 'income_type';
        }
        showLoader(); 
        let auth = BX24.getAuth(); 
        $.post('index.php', { 
            action: 'bulk_update', 
            auth: {access_token: auth.access_token, domain: auth.domain}, 
            ids: ids, 
            field: field, 
            value: val 
        }, function() { hideLoader(); deselectAll(); loadGrid(currentPage); }, 'json'); 
    }
    function bulkCreateSP() { let ids = getSelectedIds(); if(ids.length === 0) return; let type = $('#fltType').val(); if(!type) return alert('Сначала отфильтруйте список по Типу'); if(!confirm(`Создать ${ids.length} СП для типа ${type}?`)) return; showLoader(); let auth = BX24.getAuth(); $.post('index.php', { action: 'bulk_create_sp', auth: {access_token: auth.access_token, domain: auth.domain}, ids: ids, target_type: type }, function(res) { hideLoader(); deselectAll(); loadGrid(currentPage); alert('Обработано!'); console.log(res.results); }, 'json'); }

    function loadDictionaries() { let auth = BX24.getAuth(); $.post('index.php', { action: 'get_dictionaries', auth: auth }, function(res) { if(res) { expenseTypes = res.expense || []; incomeTypes = res.income || []; let optsExp = '<option value="">Все</option>'; expenseTypes.forEach(et => { optsExp += `<option value="${et.ID}">${et.NAME}</option>`; }); $('#fltExpType').html(optsExp); let optsFormExp = '<option value="">Не выбрано</option>'; expenseTypes.forEach(et => optsFormExp += `<option value="${et.ID}">${et.NAME}</option>`); $('#inpExpenseType').html(optsFormExp); let optsFormInc = '<option value="">Не выбрано</option>'; incomeTypes.forEach(it => optsFormInc += `<option value="${it.ID}">${it.NAME}</option>`); $('#inpIncomeType').html(optsFormInc); } loadGrid(1); }, 'json'); }
    function changePage(delta) { loadGrid(currentPage + delta); }
    
    function loadGrid(page) { 
        if(page < 1) page = 1; 
        showLoader(); 
        updateBulkCategorySelect();
        let auth = BX24.getAuth(); let formData = new FormData(); formData.append('action', 'load_grid'); formData.append('auth[access_token]', auth.access_token); formData.append('auth[domain]', auth.domain); formData.append('page', page); formData.append('search', $('#search').val()); formData.append('filter_payer', $('#fltPayer').val()); formData.append('filter_payee', $('#fltPayee').val()); formData.append('filter_type', $('#fltType').val()); formData.append('filter_exp_type', $('#fltExpType').val()); formData.append('filter_status', $('#fltStatus').val()); $.ajax({ url: 'index.php', type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json', success: function(res) { hideLoader(); if(res.error) { alert('PHP Error: ' + res.msg); return; } currentItems = res.items; currentPage = parseInt(res.page); $('#pageNum').text(currentPage); $('#btnPrev').prop('disabled', currentPage <= 1); $('#btnNext').prop('disabled', res.items.length < 50); if(res.stats) { $('#cntTotal').text(res.stats.total); $('#cntGreen').text(res.stats.green); $('#cntRed').text(res.stats.red); } renderGrid(res.items); }, error: function(xhr) { hideLoader(); alert('Error: ' + xhr.status); } }); }
    
    function renderGrid(items) { let html = ''; let statusFilter = $('#fltStatus').val(); items.forEach(item => { let debit = parseFloat(item[FIELDS.DEBIT] || 0); let credit = parseFloat(item[FIELDS.CREDIT] || 0); let isExpense = credit > 0; let sum = isExpense ? credit : debit; let remainder = parseFloat(item[FIELDS.REMAINDER] || 0); let totalAlloc = parseFloat(item[FIELDS.TOTAL_ALLOC] || 0); let ignore = item[FIELDS.IGNORE_FLAG] === 'Да'; let isGreen = false; let linkedIncCount = (item[FIELDS.LINK_INCOME] || []).length; if (isExpense) { if (ignore || Math.abs(remainder) < 0.01) isGreen = true; } else { if (ignore || linkedIncCount > 0) isGreen = true; } if (statusFilter === 'ok' && !isGreen) return; if (statusFilter === 'alert' && isGreen) return; let rowClass = isGreen ? 'row-green' : 'row-red'; let sumClass = isExpense ? 'text-red' : 'text-green'; let catHtml = ''; if (isExpense) { let opts = `<option value="">-</option>`; expenseTypes.forEach(et => { let sel = (item[FIELDS.EXPENSE_TYPE] == et.ID) ? 'selected' : ''; opts += `<option value="${et.ID}" ${sel}>${et.NAME}</option>`; }); catHtml = `<select class="form-select table-input" onchange="saveField(${item.id}, 'expense_type', this.value, this)">${opts}</select>`; } else { let opts = `<option value="">-</option>`; incomeTypes.forEach(it => { let val = item[FIELDS.INCOME_TYPE]; let sel = (val == it.ID) ? 'selected' : ''; opts += `<option value="${it.ID}" ${sel}>${it.NAME}</option>`; }); catHtml = `<select class="form-select table-input" onchange="saveField(${item.id}, 'income_type', this.value, this)">${opts}</select>`; } let calcOpts = `<option value="">-</option><option value="ВТБ Банк" ${item[FIELDS.CALC_TYPE]=='ВТБ Банк'?'selected':''}>ВТБ</option><option value="Материальный расчет" ${item[FIELDS.CALC_TYPE]=='Материальный расчет'?'selected':''}>Матер.</option><option value="Иной" ${item[FIELDS.CALC_TYPE]=='Иной'?'selected':''}>Иной</option>`; let calcHtml = `<select class="form-select table-input" onchange="saveField(${item.id}, 'calc_type', this.value, this)">${calcOpts}</select>`; let statusInfo = ''; if (isExpense) statusInfo = `<div class="small lh-1 mt-1">Распр: <b>${totalAlloc.toLocaleString()}</b><br>Ост: <b class="${remainder>0.01?'text-danger':'text-success'}">${remainder.toLocaleString()}</b></div>`; else statusInfo = `<div class="small mt-1">Привязано: <b>${linkedIncCount}</b></div>`; let statusContent = `<div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" ${ignore?'checked':''} onchange="saveField(${item.id}, 'ignore', this.checked, this)"><label class="form-check-label small">Не учит.</label></div>${statusInfo}`; let actions = `<button class="btn btn-sm btn-outline-secondary me-1" title="Связи" onclick="openEdit(${item.id})">🔗</button>`; if (!isExpense) actions += `<button class="btn btn-sm btn-outline-success" title="Создать СП Поступление" onclick="createIncomeSP(${item.id})">+СП</button>`; else actions += `<button class="btn btn-sm btn-outline-danger" title="Создать СП Расход" onclick="createExpenseSP(${item.id})">+СП</button>`; let p1 = renderResolvedCompany(item, '__PAYER_RESOLVED', '__PAYER_TEXT', item[FIELDS.PAYER_INN]); let p2 = renderResolvedCompany(item, '__PAYEE_RESOLVED', '__PAYEE_TEXT', item[FIELDS.PAYEE_INN]); html += `<tr class="${rowClass}" id="row_${item.id}"><td><input type="checkbox" class="row-checkbox" value="${item.id}" onchange="updateToolbar()"></td><td class="col-id">${item.id}</td><td class="small text-muted col-date">${formatDate(item[FIELDS.DATE])}<br>№ ${item[FIELDS.DOC_NUM]}</td><td class="col-company">${p1}</td><td class="col-company">${p2}</td><td class="col-purpose"><div class="text-purpose" title="${item[FIELDS.PURPOSE]}">${item[FIELDS.PURPOSE]}</div></td><td class="${sumClass} fw-bold text-end col-sum">${isExpense?'-':'+'} ${sum.toLocaleString()}</td><td class="col-calc">${calcHtml}</td><td class="col-type">${catHtml}</td><td class="col-status">${statusContent}</td><td class="text-end col-action" style="white-space:nowrap">${actions}</td></tr>`; }); $('#gridBody').html(html); batchCheckCompanies(); }
    
    function createIncomeSP(id) { if(!confirm('Создать Плановое поступление?')) return; showLoader(); let auth = BX24.getAuth(); $.post('index.php', { action: 'create_income_sp', id: id, auth: {access_token: auth.access_token, domain: auth.domain} }, function(res) { hideLoader(); if(res.success) { alert('Успешно! ID: ' + res.id); $('#row_' + id).removeClass('row-red').addClass('row-green'); loadGrid(currentPage); } else { alert('Ошибка: ' + (res.error || 'Unknown')); } }, 'json'); }
    function createExpenseSP(id) { if(!confirm('Создать Заявку на расход (с полным распределением)?')) return; showLoader(); let auth = BX24.getAuth(); $.post('index.php', { action: 'create_expense_sp', id: id, auth: {access_token: auth.access_token, domain: auth.domain} }, function(res) { hideLoader(); if(res.success) { alert('Успешно! ID: ' + res.id); $('#row_' + id).removeClass('row-red').addClass('row-green'); loadGrid(currentPage); } else { alert('Ошибка: ' + (res.error || 'Unknown')); } }, 'json'); }
    
    function saveField(id, field, value, elem) { let $el = $(elem); $el.addClass('saving-field'); let auth = BX24.getAuth(); $.post('index.php', { action: 'update_field', auth: {access_token: auth.access_token, domain: auth.domain}, id: id, field: field, value: value }, function(res) { $el.removeClass('saving-field'); if (res && res.result) { $el.addClass('saved-field'); setTimeout(() => $el.removeClass('saved-field'), 1000); loadGrid(currentPage); } else { alert('Ошибка сохранения'); } }, 'json'); }
    function openEdit(id) { $('#editForm')[0].reset(); $('#distRows').empty(); $('#incomeLinks').empty(); if (id > 0) { let item = currentItems.find(i => i.id == id); $('#editId').val(id); $('#panelTitle').text('Редактирование #' + id); $('#inpDate').val(item[FIELDS.DATE].split('T')[0]); $('#inpNum').val(item[FIELDS.DOC_NUM]); $('#inpPayer').val(item[FIELDS.PAYER]); $('#inpPayerInn').val(item[FIELDS.PAYER_INN]); $('#inpPayee').val(item[FIELDS.PAYEE]); $('#inpPayeeInn').val(item[FIELDS.PAYEE_INN]); $('#inpPurpose').val(item[FIELDS.PURPOSE]); $('#inpCalcType').val(item[FIELDS.CALC_TYPE]||'ВТБ Банк'); $('#inpExpenseType').val(item[FIELDS.EXPENSE_TYPE]); $('#inpIncomeType').val(item[FIELDS.INCOME_TYPE]); $('#inpComment').val(item[FIELDS.COMMENT]); let credit = parseFloat(item[FIELDS.CREDIT]||0); let debit = parseFloat(item[FIELDS.DEBIT]||0); if(credit > 0){ $('#inpOpType').val('expense'); $('#inpAmount').val(credit); $('#inpIgnore').prop('checked', item[FIELDS.IGNORE_FLAG]==='Да'); let j=[]; try{j=JSON.parse(item[FIELDS.JSON_SPLIT]);}catch(e){} if(j.length===0)j.push({}); j.forEach(r=>addDistRow(r.id,r.amount,r.category)); } else { $('#inpOpType').val('income'); $('#inpAmount').val(debit); $('#inpIgnoreInc').prop('checked', item[FIELDS.IGNORE_FLAG]==='Да'); let ids=item[FIELDS.LINK_INCOME]||[]; renderIncomeLinks(ids); } } else { $('#panelTitle').text('Новый'); $('#editId').val(0); $('#inpCalcType').val('Иной'); $('#inpOpType').val('expense'); addDistRow(); } toggleDistMode(); editOffcanvas = new bootstrap.Offcanvas(document.getElementById('panel')); editOffcanvas.show(); }
    function toggleDistMode() { let t=$('#inpOpType').val(); if(t==='expense'){ $('#blockExpense').show(); $('#blockIncome').hide(); $('#blockExpCat').show(); $('#blockIncCat').hide(); recalcPanel(); } else { $('#blockExpense').hide(); $('#blockIncome').show(); $('#blockExpCat').hide(); $('#blockIncCat').show(); } }
    function addDistRow(id='',am='', catId='') { let r=Math.floor(Math.random()*99999); let catOpts = `<option value="">Категория...</option>`; expenseTypes.forEach(et => { let sel = (catId == et.ID) ? 'selected' : ''; catOpts += `<option value="${et.ID}" ${sel}>${et.NAME}</option>`; }); let html = `<div class="row g-1 mb-2 dist-row align-items-center" id="dr_${r}"><div class="col-5"><div class="input-group input-group-sm"><button type="button" class="btn btn-outline-secondary" onclick="triggerExpenseSelector(${r})">Выбрать</button><input type="text" class="form-control dist-title" readonly value="${id?'ID: '+id:''}"><input type="hidden" class="dist-id" value="${id}"></div></div><div class="col-4"><select class="form-select form-select-sm dist-cat">${catOpts}</select></div><div class="col-2"><input type="number" class="form-control form-control-sm dist-amount" value="${am}" oninput="recalcPanel()" placeholder="Сумма"></div><div class="col-1 text-end"><span class="text-danger cursor-pointer" onclick="$('#dr_${r}').remove();recalcPanel()">✖</span></div></div>`; $('#distRows').append(html); }
    function triggerExpenseSelector(r) { openCustomSelector(SP_EXP, function(item) { $(`#dr_${r} .dist-id`).val(item.id); $(`#dr_${r} .dist-title`).val(item.title); }); }
    function triggerIncomeSelector() { openCustomSelector(SP_INC, function(item) { if(currentIncomeLinks.indexOf(String(item.id)) === -1 && currentIncomeLinks.indexOf(Number(item.id)) === -1) { currentIncomeLinks.push(item.id); renderIncomeLinks(currentIncomeLinks); } }); }
    function recalcPanel(){ let t=parseFloat($('#inpAmount').val())||0; let d=0; $('.dist-amount').each(function(){d+=parseFloat($(this).val())||0;}); let rm=t-d; $('#lblDist').text(d.toFixed(2)); $('#lblRem').text(rm.toFixed(2)); if(rm<-0.01)$('#lblRem').addClass('text-danger');else $('#lblRem').removeClass('text-danger');}
    function saveItem() { 
        let id=$('#editId').val(); let url='index.php'; let auth=BX24.getAuth(); 
        let data={action:'save_item',auth:{access_token:auth.access_token,domain:auth.domain},id:id,
            date:$('#inpDate').val(),doc_num:$('#inpNum').val(),payer:$('#inpPayer').val(),payer_inn:$('#inpPayerInn').val(),
            payee:$('#inpPayee').val(),payee_inn:$('#inpPayeeInn').val(),purpose:$('#inpPurpose').val(),calc_type:$('#inpCalcType').val(),
            comment:$('#inpComment').val(),expense_type:$('#inpExpenseType').val(),income_type:$('#inpIncomeType').val(),
            amount:$('#inpAmount').val(),op_type:$('#inpOpType').val()};
        if($('#inpOpType').val() === 'expense'){
            let totalAlloc=0; let jsonData=[];
            $('.dist-row').each(function(){
                let did=$(this).find('.dist-id').val(); let dam=$(this).find('.dist-amount').val(); let dcat=$(this).find('.dist-cat').val();
                if(dam>0){ totalAlloc+=parseFloat(dam); jsonData.push({id:did,amount:dam,category:dcat}); }
            });
            let splitReadable = jsonData.map(j => (j.id ? "ID "+j.id : "Без ID") + " ("+j.amount+")").join('; ');
            data.json_data=JSON.stringify(jsonData); data.split_readable=splitReadable; data.total_alloc=totalAlloc; 
            data.remainder=parseFloat($('#inpAmount').val())-totalAlloc; data.linked_expense=jsonData.map(j=>j.id).filter(v=>!!v);
            data.ignore=$('#inpIgnore').prop('checked');
        } else {
            data.linked_income=currentIncomeLinks; data.ignore=$('#inpIgnoreInc').prop('checked');
            data.total_alloc=0; data.remainder=0; data.json_data='[]'; data.split_readable='';
        }
        showLoader();
        $.post(url, data, function(res){ 
            hideLoader(); 
            if(res && res.result) { 
                console.log('DEBUG INFO:', res.debug); 
                editOffcanvas.hide(); 
                loadGrid(currentPage); 
            } else { 
                alert('Ошибка сохранения: '+(res.error_description||JSON.stringify(res))); 
            } 
        }, 'json');
    }
    function renderIncomeLinks(ids){
        currentIncomeLinks=ids||[];
        if(!ids||!ids.length){$('#incomeLinks').html('<small>Нет</small>');return;}
        // Changed render to include remove button (x)
        $('#incomeLinks').html(ids.map(id=>`
            <span class="badge bg-success me-1 mb-1 p-2" style="cursor: default">
                ID ${id} 
                <span class="ms-2 text-white" style="cursor: pointer; font-weight: bold;" onclick="removeIncomeLink(${id})">&times;</span>
            </span>
        `).join(''));
    }
    // New function to remove link
    function removeIncomeLink(idToRemove) {
        currentIncomeLinks = currentIncomeLinks.filter(id => id != idToRemove);
        renderIncomeLinks(currentIncomeLinks);
    }

    // REFUND FUNCTION
    function addRefund() {
        let type = $('#inpOpType').val();
        let filterMode = (type === 'expense') ? 'debit_only' : 'credit_only';
        openCustomSelector(SP_ID, function(selectedPayment) {
            if (!confirm(`Сделать возврат по платежу #${selectedPayment.id} (${selectedPayment.title})?`)) return;
            let targetId = $('#editId').val();
            if(targetId == 0) { alert('Сначала сохраните элемент!'); return; }

            showLoader();
            let auth = BX24.getAuth();
            $.post('index.php', {
                action: 'process_refund',
                auth: {access_token: auth.access_token, domain: auth.domain},
                target_id: targetId,
                payment_id: selectedPayment.id,
                target_type: type
            }, function(res) {
                hideLoader();
                if (res.success) {
                    alert('Возврат успешно добавлен!');
                    $('#inpAmount').val(res.new_amount);
                    recalcPanel(); 
                    loadGrid(currentPage);
                    setTimeout(() => openEdit(targetId), 500); 
                } else {
                    alert('Ошибка: ' + res.msg);
                }
            }, 'json');
        }, filterMode);
    }

    function openImport(){ new bootstrap.Modal(document.getElementById('importModal')).show(); }
    function runImport(){ let fd=new FormData($('#importForm')[0]); let auth=BX24.getAuth(); fd.append('auth[access_token]',auth.access_token); fd.append('auth[domain]',auth.domain); showLoader(); $.ajax({url:'index.php',type:'POST',data:fd,processData:false,contentType:false,dataType:'json',success:function(res){ hideLoader(); alert('Импорт: '+res.imported+', Дубликатов: '+res.duplicates); loadGrid(1); bootstrap.Modal.getInstance(document.getElementById('importModal')).hide(); }}); }
    function createCo(inn,title){ if(!confirm(`Создать ${title}?`))return; let auth=BX24.getAuth(); $.post('index.php',{action:'create_company',inn:inn,title:title,auth:auth},function(){loadGrid(currentPage);}); }
</script>
</body>
</html>