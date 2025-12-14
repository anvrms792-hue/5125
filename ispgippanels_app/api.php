<?php
// ============================================================================
// API PROXY: UNIVERSAL v11.5 (JSON SYNTAX FIX + LOGGING)
// ============================================================================

// ОТКЛЮЧАЕМ ВЫВОД ОШИБОК В ПОТОК (Пишем только в лог)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
error_reporting(E_ALL);

set_time_limit(0); 
ignore_user_abort(true); 
ini_set('max_execution_time', 0);
ini_set('memory_limit', '2048M');

header('Content-Type: application/json');

// 1. CORS
$allowedOrigins = ['https://for-apps.ru', 'https://vvstudio.bitrix24.ru'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// 2. КОНФИГУРАЦИЯ


define('FAKE_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

// Константы сущностей
define('YA_ROOT', '/Загрузки_из_Приложения'); 
define('SP_SUBCONTRACTOR', 1266);
define('SP_STAGES', 1290);
define('SP_EXPENSES', 1304);
define('SP_TENDERS', 1300);
define('SP_RESULTS', 1334);
define('SP_STAGE_PRODUCTS', 1342);
define('SP_SUBTASKS', 1346);

// Поля
$FIELDS = [
    // --- СДЕЛКИ ---
    'OBJ_NAME'        => 'UF_CRM_1761037276',
    'OBJ_ADDR'        => 'UF_CRM_1761037367',
    'FOLDER_TZ'       => 'UF_CRM_1764865114',
    'FOLDER_OTHER'    => 'UF_CRM_1764867444',
    'FOLDER_RESULTS'  => 'UF_CRM_1764928487',
    
    // --- ЛИДЫ ---
    'LEAD_OBJ_NAME'   => 'UF_CRM_1735933101096',
    'LEAD_OBJ_ADDR'   => 'UF_CRM_1735933109497',
    'LEAD_FOLDER_TZ'  => 'UF_CRM_1765471675',
    'LEAD_FOLDER_OTHER' => 'UF_CRM_1765471699',

    // --- ОБЩЕЕ ---
    'USER_LINK'       => 'UF_CRM_1764543173',
    'MANAGER_LINK'    => 'UF_CRM_1765452123',
    'WORK_AREAS'      => 'UF_CRM_1765451777',
    'STAGE_LINK_SUB'  => 'ufCrm86_1764881202',
    'EXP_LINK_SUB'    => 'ufCrm92_1763844515',
    'STAGE_PRODUCTS'  => 'ufCrm86_1763845210',
    'STAGE_DEADLINE'  => 'ufCrm86_1763847030',
    'STAGE_DEPS'      => 'ufCrm86_1763846881',
    'STAGE_STATUS'    => 'ufCrm86_1763860705',
    
    'RES_LINK_STAGE'  => 'ufCrm106_1764863399',
    'RES_LINK_SUB'    => 'ufCrm106_1764863420',
    'RES_GIP_COMM'    => 'ufCrm106_1764863382',
    'RES_PRODUCT'     => 'ufCrm106_1764863344',
    'RES_COMMENT'     => 'ufCrm106_1764863374',
    'RES_VERSION'     => 'ufCrm106_1764931105',
    'RES_FOLDER_PATH' => 'ufCrm106_1765058469',
    
    // --- НОВЫЕ СП: ТОВАРЫ ЭТАПА И ПОДЗАДАЧИ ---
    'STAGE_PRODUCT_LINK_STAGE'    => 'ufCrm110_1765572811',
    'STAGE_PRODUCT_LINK_PRODUCT'  => 'ufCrm110_1765572838',
    'STAGE_PRODUCT_NAME'          => 'ufCrm110_1765572846',
    'STAGE_PRODUCT_SORT_ORDER'    => 'ufCrm110_1765572854',
    'STAGE_PRODUCT_DEADLINE_JURE' => 'ufCrm110_1765572860',
    'STAGE_PRODUCT_STATUS_JURE'   => 'ufCrm110_1765572878',
    'STAGE_PRODUCT_DEADLINE_FACTO'=> 'ufCrm110_1765572889',
    'STAGE_PRODUCT_STATUS_FACTO'  => 'ufCrm110_1765572898',
    
    'SUBTASK_LINK_STAGE_PRODUCT'  => 'ufCrm112_1765572991',
    'SUBTASK_TASK_TYPE'           => 'ufCrm112_1765573009',
    'SUBTASK_DESCRIPTION'         => 'ufCrm112_1765573015',
    'SUBTASK_LINK_RESULTS'        => 'ufCrm112_1765573022',
    'SUBTASK_STATUS'              => 'ufCrm112_1765573037',
    'SUBTASK_PRIORITY'            => 'ufCrm112_1765573044',
    'SUBTASK_DUE_DATE'            => 'ufCrm112_1765573073',
    'SUBTASK_VERSION'             => 'ufCrm112_1765573080',
    
    // ТЕНДЕРЫ
    'TEND_EXECUTOR'   => 'ufCrm90_1763033396',
    'TEND_SUM'        => 'ufCrm90_1763033407',
    'TEND_COMMENT'    => 'ufCrm90_1763033426',
    'TEND_WORK'       => 'ufCrm90_1763033438',
    'TEND_DIRECTION'  => 'ufCrm90_1765454416',
    'TEND_MANAGERS'   => 'ufCrm90_1765570487',
    'TEND_LINK_DEAL'  => 'parentId2',
    'TEND_LINK_LEAD'  => 'parentId1'  
];

$ALLOWED_SECTION_IDS = [54,82,38,40,58,60,62,64,86,88,90,92,94,96,68,70,72,74,76,78,80,112];

function writeLog($data) {
    file_put_contents('log.txt', date('Y-m-d H:i:s') . " | " . print_r($data, true) . "\n------------------------\n", FILE_APPEND);
}

$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
$input = [];
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$action = $input['action'] ?? '';
$userId = $input['userId'] ?? 0;

if (!$userId && $action !== 'getTenders') { echo json_encode(['error' => 'User ID required']); exit; }

try {
    switch ($action) {
        
        case 'getInitialData':
            // 1. Ищем как Руководителя
            $role = 'employee'; $allCompanies = [];
            $managerCompanies = callB24('crm.company.list', [ 'filter' => [ $FIELDS['MANAGER_LINK'] => $userId ], 'select' => ['ID', 'TITLE', $FIELDS['WORK_AREAS']] ]);
            
            if (!empty($managerCompanies)) { 
                $role = 'manager'; 
                $allCompanies = $managerCompanies;
            } else {
                // 2. Ищем как Сотрудника
                $employeeCompanies = callB24('crm.company.list', [ 'filter' => [ $FIELDS['USER_LINK'] => $userId ], 'select' => ['ID', 'TITLE', $FIELDS['WORK_AREAS']] ]);
                if (!empty($employeeCompanies)) $allCompanies = $employeeCompanies;
            }
            
            if (empty($allCompanies)) { echo json_encode(['empty' => true, 'message' => 'Компания не найдена']); exit; }
            
            $companyIds = array_column($allCompanies, 'ID');
            $currentWorkAreas = [];
            if (!empty($allCompanies)) {
                $currentWorkAreas = $allCompanies[0][$FIELDS['WORK_AREAS']] ?? [];
            }
            
            // Загружаем данные по ВСЕ компаниям
            $allSubItems = [];
            foreach ($companyIds as $compId) {
                $tmpSub = callB24('crm.item.list', ['entityTypeId' => SP_SUBCONTRACTOR, 'filter' => [ 'companyId' => $compId ], 'select' => ['id', 'title', 'parentId2']]);
                $subItems = ensureArray($tmpSub);
                $allSubItems = array_merge($allSubItems, $subItems);
            }
            
            $subIds = array_column($allSubItems, 'id');
            $stages = []; $deals = []; $expenses = [];
            
            if (!empty($subIds)) {
                $tmpStages = callB24('crm.item.list', ['entityTypeId' => SP_STAGES, 'filter' => [ '@'.$FIELDS['STAGE_LINK_SUB'] => $subIds ], 'select' => ['id', 'title', $FIELDS['STAGE_LINK_SUB'], $FIELDS['STAGE_DEADLINE'], $FIELDS['STAGE_PRODUCTS'], $FIELDS['STAGE_DEPS'], $FIELDS['STAGE_STATUS'], 'stageId']]);
                $stages = ensureArray($tmpStages);
                $dealIds = array_unique(array_filter(array_column($allSubItems, 'parentId2')));
                if (!empty($dealIds)) {
                    $rawDeals = callB24('crm.deal.list', ['filter' => [ '@ID' => $dealIds ], 'select' => ['ID', 'TITLE', 'STAGE_ID', $FIELDS['OBJ_NAME'], $FIELDS['OBJ_ADDR'], $FIELDS['FOLDER_TZ'], $FIELDS['FOLDER_OTHER'], $FIELDS['FOLDER_RESULTS']]]);
                    $rawDeals = ensureArray($rawDeals);
                    foreach ($rawDeals as $d) {
                        $path = $d[$FIELDS['FOLDER_RESULTS']] ?? null;
                        if ($path && strpos($path, '/') !== 0) $path = null;
                        $d['YANDEX_ROOT_PATH'] = $path; 
                        $tzPath = $d[$FIELDS['FOLDER_TZ']] ?? null;
                        if ($tzPath && strpos($tzPath, '/') !== 0) $d[$FIELDS['FOLDER_TZ']] = null;
                        $otherPath = $d[$FIELDS['FOLDER_OTHER']] ?? null;
                        if ($otherPath && strpos($otherPath, '/') !== 0) $d[$FIELDS['FOLDER_OTHER']] = null;
                        $deals[] = $d;
                    }
                }
                $tmpExp = callB24('crm.item.list', ['entityTypeId' => SP_EXPENSES, 'filter' => [ '@'.$FIELDS['EXP_LINK_SUB'] => $subIds ], 'select' => ['title', 'opportunity', 'stageId']]);
                $expenses = ensureArray($tmpExp);
            }
            echo json_encode([ 'role' => $role, 'companyId' => $companyIds[0], 'workAreas' => $currentWorkAreas, 'subcontracts' => $allSubItems, 'stages' => $stages, 'deals' => $deals, 'expenses' => $expenses ]);
            break;

        case 'getLeadsForGIP':
            writeLog("--- GET LEADS (SAFE) ---");
            $res = callB24('crm.lead.list', [
                'order' => ['ID' => 'DESC'],
                'select' => [ 'ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID', 'STATUS_ID', 'STATUS_SEMANTIC_ID', $FIELDS['LEAD_OBJ_NAME'], $FIELDS['LEAD_OBJ_ADDR'], $FIELDS['LEAD_FOLDER_TZ'], $FIELDS['LEAD_FOLDER_OTHER'] ]
            ]);
            // Фильтруем в PHP, чтобы избежать ошибок API
            $items = ensureArray($res);
            $filteredItems = [];
            foreach ($items as $item) {
                if ($item['STATUS_SEMANTIC_ID'] === 'S' || $item['STATUS_SEMANTIC_ID'] === 'F') continue;
                $objName = $item[$FIELDS['LEAD_OBJ_NAME']] ?? '';
                if (!empty($objName)) { $filteredItems[] = $item; }
            }
            writeLog("Leads found: " . count($filteredItems));
            echo json_encode($filteredItems);
            break;

        case 'createTenderSmart':
            writeLog("--- START CREATE TENDER ---");
            $sourceId = $input['dealId']; 
            $sourceType = $input['entityType'] ?? 'deal';
            $productName = $input['productName'];
            $productId = $input['productId'];
            
            $prodData = callB24('crm.product.get', ['id' => $productId]);
            if (empty($prodData)) {
                $catData = callB24('catalog.product.get', ['id' => $productId]);
                if (!empty($catData['product'])) {
                    $prodData = $catData['product'];
                    $prodData['SECTION_ID'] = $catData['product']['iblockSectionId'] ?? null;
                }
            }
            if (empty($prodData['SECTION_ID'])) { echo json_encode(['error' => 'У товара нет раздела']); exit; }
            $sectionId = $prodData['SECTION_ID'];

            $users = callB24('user.get', ['FILTER' => ['UF_DEPARTMENT' => DEPT_ID_EXECUTORS]]);
            $users = ensureArray($users);
            if (empty($users)) { echo json_encode(['error' => 'В отделе нет пользователей']); exit; }
            $userIds = array_column($users, 'ID');

            $companies = callB24('crm.company.list', [
                'filter' => [ $FIELDS['WORK_AREAS'] => $sectionId ],
                'select' => ['ID', 'TITLE', $FIELDS['MANAGER_LINK']]
            ]);
            $companies = ensureArray($companies);
            if (empty($companies)) { echo json_encode(['error' => 'Нет исполнителей с таким направлением.']); exit; }

            $createdCount = 0;
            $processedUsers = []; 

            // Собираем всех подходящих руководителей из всех компаний
            $allManagerIds = [];
            $executorCompanies = [];
            
            foreach ($companies as $comp) {
                $managers = $comp[$FIELDS['MANAGER_LINK']] ?? [];
                if (!is_array($managers)) $managers = [$managers];
                
                foreach ($managers as $mgrId) {
                    if (in_array($mgrId, $userIds) && !in_array($mgrId, $allManagerIds)) {
                        $allManagerIds[] = $mgrId;
                        if (!in_array($comp['TITLE'], $executorCompanies)) {
                            $executorCompanies[] = $comp['TITLE'];
                        }
                    }
                }
            }
            
            // Если есть подходящие руководители - создаем ОДИН тендер и назначаем всех
            if (!empty($allManagerIds)) {
                $fields = [
                    'title' => "Тендер: $productName",
                    'assignedById' => $allManagerIds[0],
                    $FIELDS['TEND_MANAGERS'] => $allManagerIds,
                    $FIELDS['TEND_EXECUTOR'] => implode(', ', $executorCompanies),
                    $FIELDS['TEND_WORK'] => $productName,
                    $FIELDS['TEND_DIRECTION'] => $sectionId,
                    'opportunity' => 0,
                    'stageId' => 'DT1300_114:NEW'
                ];
                if ($sourceType === 'lead') { $fields['parentId1'] = $sourceId; } 
                else { $fields['parentId2'] = $sourceId; }
                
                $res = callB24('crm.item.add', [ 'entityTypeId' => SP_TENDERS, 'fields' => $fields ]);
                if (is_array($res) && isset($res['item'])) { $createdCount = 1; }
            }
            echo json_encode(['success' => true, 'count' => $createdCount]);
            break;

        case 'getStageProducts':
            // Загружаем товары этапа с подзадачами
            $stageId = isset($input['stageId']) ? (int)$input['stageId'] : 0;
            if (!$stageId) { echo json_encode(['error' => 'Stage ID required']); exit; }

            try {
                // Получаем товары этапа
                $stageProductsResp = callB24('crm.item.list', [
                    'entityTypeId' => SP_STAGE_PRODUCTS,
                    'filter' => [ $FIELDS['STAGE_PRODUCT_LINK_STAGE'] => $stageId ],
                    'order' => [ $FIELDS['STAGE_PRODUCT_SORT_ORDER'] => 'ASC' ],
                    'select' => [
                        'id', 'title', 
                        $FIELDS['STAGE_PRODUCT_LINK_PRODUCT'],
                        $FIELDS['STAGE_PRODUCT_NAME'],
                        $FIELDS['STAGE_PRODUCT_SORT_ORDER'],
                        $FIELDS['STAGE_PRODUCT_DEADLINE_JURE'],
                        $FIELDS['STAGE_PRODUCT_STATUS_JURE'],
                        $FIELDS['STAGE_PRODUCT_DEADLINE_FACTO'],
                        $FIELDS['STAGE_PRODUCT_STATUS_FACTO']
                    ]
                ]);
                
                if (isset($stageProductsResp['error'])) {
                    echo json_encode(['error' => 'API Error: ' . $stageProductsResp['error']]);
                    exit;
                }
                
                $stageProducts = ensureArray($stageProductsResp);

                $result = [];
                foreach ($stageProducts as $sp) {
                    $productId = $sp['id'];
                    
                    // Получаем подзадачи этого товара
                    $subtasksResp = callB24('crm.item.list', [
                        'entityTypeId' => SP_SUBTASKS,
                        'filter' => [ $FIELDS['SUBTASK_LINK_STAGE_PRODUCT'] => $productId ],
                        'select' => [
                            'id', 'title',
                            $FIELDS['SUBTASK_TASK_TYPE'],
                            $FIELDS['SUBTASK_DESCRIPTION'],
                            $FIELDS['SUBTASK_STATUS'],
                            $FIELDS['SUBTASK_PRIORITY'],
                            $FIELDS['SUBTASK_DUE_DATE'],
                            $FIELDS['SUBTASK_LINK_RESULTS']
                        ]
                    ]);
                    
                    $subtasks = ensureArray($subtasksResp);

                    $result[] = [
                        'id' => $productId,
                        'title' => $sp['title'],
                        'product_name' => $sp[$FIELDS['STAGE_PRODUCT_NAME']] ?? '',
                        'sort_order' => $sp[$FIELDS['STAGE_PRODUCT_SORT_ORDER']] ?? 0,
                        'deadline_jure' => $sp[$FIELDS['STAGE_PRODUCT_DEADLINE_JURE']] ?? null,
                        'status_jure' => $sp[$FIELDS['STAGE_PRODUCT_STATUS_JURE']] ?? 'Не начинался',
                        'deadline_facto' => $sp[$FIELDS['STAGE_PRODUCT_DEADLINE_FACTO']] ?? null,
                        'status_facto' => $sp[$FIELDS['STAGE_PRODUCT_STATUS_FACTO']] ?? 'Не начинался',
                        'subtasks' => array_map(function($st) use ($FIELDS) {
                            return [
                                'id' => $st['id'],
                                'title' => $st['title'],
                                'task_type' => $st[$FIELDS['SUBTASK_TASK_TYPE']] ?? 'Прочее',
                                'description' => $st[$FIELDS['SUBTASK_DESCRIPTION']] ?? '',
                                'status' => $st[$FIELDS['SUBTASK_STATUS']] ?? 'Не начинался',
                                'priority' => $st[$FIELDS['SUBTASK_PRIORITY']] ?? 'Обычный',
                                'due_date' => $st[$FIELDS['SUBTASK_DUE_DATE']] ?? null,
                                'results' => is_array($st[$FIELDS['SUBTASK_LINK_RESULTS']] ?? null) ? $st[$FIELDS['SUBTASK_LINK_RESULTS']] : [$st[$FIELDS['SUBTASK_LINK_RESULTS']] ?? null]
                            ];
                        }, $subtasks)
                    ];
                }

                echo json_encode($result);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
            }
            break;

        case 'updateSubtaskStatus':
            // Обновляем статус подзадачи
            $subtaskId = isset($input['subtaskId']) ? (int)$input['subtaskId'] : 0;
            $newStatus = isset($input['status']) ? $input['status'] : 'Не начинался';
            if (!$subtaskId) { echo json_encode(['error' => 'Subtask ID required']); exit; }

            try {
                $updateResult = callB24('crm.item.update', [
                    'entityTypeId' => SP_SUBTASKS,
                    'id' => $subtaskId,
                    'fields' => [
                        $FIELDS['SUBTASK_STATUS'] => $newStatus,
                        $FIELDS['SUBTASK_VERSION'] => 1  // Инкрементируем версию
                    ]
                ]);
                echo json_encode(['success' => true, 'id' => $subtaskId, 'newStatus' => $newStatus]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        case 'getStagesForLead':
            // Получаем этапы для лида (для панели ГИПа)
            $leadId = isset($input['leadId']) ? (int)$input['leadId'] : 0;
            if (!$leadId) { echo json_encode(['error' => 'Lead ID required']); exit; }

            try {
                // Получаем субподрядчиков этого лида
                $subcontractsResp = callB24('crm.item.list', [
                    'entityTypeId' => SP_SUBCONTRACTOR,
                    'filter' => [ 'parentId1' => $leadId ],
                    'select' => ['id']
                ]);
                
                $subcontracts = ensureArray($subcontractsResp);

                if (empty($subcontracts)) { echo json_encode([]); exit; }

                $subIds = array_column($subcontracts, 'id');
                
                // Получаем этапы, привязанные к этим субподрядчикам
                $stagesResp = callB24('crm.item.list', [
                    'entityTypeId' => SP_STAGES,
                    'filter' => [ '@' . $FIELDS['STAGE_LINK_SUB'] => $subIds ],
                    'select' => [
                        'id', 'title',
                        $FIELDS['STAGE_LINK_SUB'],
                        $FIELDS['STAGE_DEADLINE'],
                        $FIELDS['STAGE_STATUS']
                    ]
                ]);
                
                $stages = ensureArray($stagesResp);

                echo json_encode(array_map(function($s) use ($FIELDS) {
                    return [
                        'id' => $s['id'],
                        'title' => $s['title'],
                        'deadline' => $s[$FIELDS['STAGE_DEADLINE']] ?? null,
                        'status' => $s[$FIELDS['STAGE_STATUS']] ?? 'Не начинался'
                    ];
                }, $stages));
            } catch (Exception $e) {
                echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
            }
            break;

        case 'getTenders':
            $res = callB24('crm.item.list', [
                'entityTypeId' => SP_TENDERS,
                'filter' => [ 'assignedById' => $userId ],
                'select' => ['id', 'title', 'createdTime', 'parentId1', 'parentId2', $FIELDS['TEND_WORK'], 'stageId']
            ]);
            $rawItems = ensureArray($res);
            if (empty($rawItems)) { echo json_encode([]); exit; }

            $items = []; $dealIds = []; $leadIds = [];

            foreach ($rawItems as $item) {
                if ($item['stageId'] !== 'DT1300_114:FAIL' && $item['stageId'] !== 'DT1300_114:SUCCESS') {
                    $items[] = $item;
                    if ($item['parentId2']) $dealIds[] = $item['parentId2'];
                    if ($item['parentId1']) $leadIds[] = $item['parentId1'];
                }
            }
            
            $dealIds = array_unique($dealIds); $leadIds = array_unique($leadIds);
            $entitiesInfo = [];

            if (!empty($dealIds)) {
                $dRes = callB24('crm.deal.list', [ 'filter' => ['@ID' => $dealIds], 'select' => ['ID', 'TITLE', $FIELDS['OBJ_NAME'], $FIELDS['OBJ_ADDR'], $FIELDS['FOLDER_TZ']] ]);
                if (is_array($dRes)) {
                    foreach ($dRes as $d) {
                        $path = $d[$FIELDS['FOLDER_TZ']] ?? null;
                        if ($path && strpos($path, '/') !== 0) $path = null;
                        $entitiesInfo['deal_'.$d['ID']] = [ 'objName' => $d[$FIELDS['OBJ_NAME']] ?: $d['TITLE'], 'address' => $d[$FIELDS['OBJ_ADDR']] ?: '-', 'folderId' => $path ];
                    }
                }
            }

            if (!empty($leadIds)) {
                $lRes = callB24('crm.lead.list', [ 'filter' => ['@ID' => $leadIds], 'select' => ['ID', 'TITLE', $FIELDS['LEAD_OBJ_NAME'], $FIELDS['LEAD_OBJ_ADDR'], $FIELDS['LEAD_FOLDER_TZ']] ]);
                if (is_array($lRes)) {
                    foreach ($lRes as $l) {
                        $path = $l[$FIELDS['LEAD_FOLDER_TZ']] ?? null;
                        if ($path && strpos($path, '/') !== 0) $path = null;
                        $entitiesInfo['lead_'.$l['ID']] = [ 'objName' => $l[$FIELDS['LEAD_OBJ_NAME']] ?: $l['TITLE'], 'address' => $l[$FIELDS['LEAD_OBJ_ADDR']] ?: '-', 'folderId' => $path ];
                    }
                }
            }
            
            $result = [];
            foreach ($items as $item) {
                $key = $item['parentId1'] ? 'lead_'.$item['parentId1'] : 'deal_'.$item['parentId2'];
                $info = $entitiesInfo[$key] ?? ['objName'=>'Неизвестный объект','address'=>'-','folderId'=>null];
                $result[] = [ 'id' => $item['id'], 'workName' => $item[$FIELDS['TEND_WORK']], 'date' => $item['createdTime'], 'objName' => $info['objName'], 'address' => $info['address'], 'folderPath' => $info['folderId'], 'status' => $item['stageId'] ];
            }
            echo json_encode($result);
            break;

        case 'submitTenderResponse':
            $itemId = $input['itemId'];
            callB24('crm.item.update', [
                'entityTypeId' => SP_TENDERS,
                'id' => $itemId,
                'fields' => [
                    $FIELDS['TEND_SUM'] => $input['price'],
                    $FIELDS['TEND_COMMENT'] => $input['comment'],
                    'opportunity' => $input['price'],
                    'stageId' => 'DT1300_114:PREPARATION'
                ]
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'getTendersForGIP':
            $dealId = $input['dealId'];
            $entityType = $input['entityType'] ?? 'deal';
            $filter = [];
            if ($entityType === 'lead') $filter = [ 'parentId1' => $dealId ];
            else $filter = [ 'parentId2' => $dealId ];
            $res = callB24('crm.item.list', [
                'entityTypeId' => SP_TENDERS,
                'filter' => $filter,
                'select' => [ 'id', 'title', 'stageId', $FIELDS['TEND_EXECUTOR'], $FIELDS['TEND_SUM'], $FIELDS['TEND_COMMENT'], $FIELDS['TEND_WORK'] ],
                'order' => ['id' => 'DESC']
            ]);
            echo json_encode(ensureArray($res));
            break;

        case 'selectTenderWinner':
            writeLog("--- SELECT WINNER ---");
            $itemId = $input['itemId'];
            $tmpWinner = callB24('crm.item.get', ['entityTypeId' => SP_TENDERS, 'id' => $itemId]);
            $winner = is_array($tmpWinner) && isset($tmpWinner['item']) ? $tmpWinner['item'] : $tmpWinner;
            $workName = html_entity_decode(trim($winner[$FIELDS['TEND_WORK']])); 
            
            $parentIdField = $winner['parentId1'] ? 'parentId1' : 'parentId2';
            $parentId = $winner[$parentIdField];
            $winnerUserId = $winner['assignedById'];
            $price = $winner[$FIELDS['TEND_SUM']];
            
            $objName = 'Объект';
            if ($parentIdField === 'parentId2') { 
                $deal = callB24('crm.deal.get', ['id' => $parentId]);
                $objName = $deal[$FIELDS['OBJ_NAME']] ?: $deal['TITLE'];
            } else { 
                $lead = callB24('crm.lead.get', ['id' => $parentId]);
                $objName = $lead[$FIELDS['LEAD_OBJ_NAME']] ?: $lead['TITLE'];
            }

            callB24('crm.item.update', [ 'entityTypeId' => SP_TENDERS, 'id' => $itemId, 'fields' => [ 'stageId' => 'DT1300_114:SUCCESS' ] ]);

            if ($winnerUserId) {
                $msg = "Поздравляем, вас выбрали в качестве исполнителя по объекту\n[B]{$objName}[/B]\nРабота: {$workName}\nСтоимость: " . number_format((float)$price, 0, '.', ' ') . " ₽\n\nВ ближайшее время с вами свяжется наш представитель.";
                callB24('im.message.add', ['DIALOG_ID' => $winnerUserId, 'MESSAGE' => $msg]);
            }

            $tmpAllTenders = callB24('crm.item.list', [ 
                'entityTypeId' => SP_TENDERS, 
                'filter' => [ $parentIdField => $parentId ], 
                'select' => ['id', 'stageId', $FIELDS['TEND_WORK']] 
            ]);
            $allTenders = ensureArray($tmpAllTenders);

            foreach ($allTenders as $t) {
                if ($t['id'] != $itemId && !in_array($t['stageId'], ['DT1300_114:SUCCESS', 'DT1300_114:FAIL'])) {
                    $currentWork = html_entity_decode(trim($t[$FIELDS['TEND_WORK']]));
                    if ($currentWork == $workName) {
                        callB24('crm.item.update', [ 'entityTypeId' => SP_TENDERS, 'id' => $t['id'], 'fields' => [ 'stageId' => 'DT1300_114:FAIL' ] ]);
                    }
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'getSections':
            $rawSections = callB24('crm.productsection.list', [ 'order' => ['NAME' => 'ASC'], 'filter' => [ 'CATALOG_ID' => 14 ], 'select' => ['ID', 'NAME'] ]);
            $sectionMap = []; if (is_array($rawSections)) { foreach ($rawSections as $sec) { $sectionMap[$sec['ID']] = $sec; } }
            $sortedSections = []; foreach ($ALLOWED_SECTION_IDS as $id) { if (isset($sectionMap[$id])) { $sortedSections[] = $sectionMap[$id]; } }
            echo json_encode($sortedSections);
            break;
        case 'saveCompanyProfile':
            $companyId = $input['companyId']; $areas = $input['areas'] ?? []; $validAreas = array_intersect($areas, $ALLOWED_SECTION_IDS);
            $res = callB24('crm.company.update', ['ID' => $companyId, 'fields' => [ $FIELDS['WORK_AREAS'] => $validAreas ]]);
            if ($res) echo json_encode(['success' => true]); else echo json_encode(['error' => 'Ошибка сохранения']);
            break;
        case 'getYandexList':
            $path = $input['path'] ?? ''; if (!$path) { echo json_encode([]); exit; }
            $res = yandexReq('GET', '?path=' . urlencode($path) . '&limit=100');
            $items = []; if (isset($res['_embedded']['items'])) { foreach ($res['_embedded']['items'] as $item) { $items[] = [ 'NAME' => $item['name'], 'TYPE' => $item['type'] === 'dir' ? 'folder' : 'file', 'ID' => $item['path'], 'URL' => $item['file'] ?? null ]; } }
            echo json_encode($items);
            break;
        case 'downloadFolderLink':
            $path = $input['path']; if (!$path) { echo json_encode(['error' => 'Путь не указан']); exit; }
            yandexReq('PUT', '/publish', ['path' => $path]); $info = yandexReq('GET', '', ['path' => $path]);
            if (!empty($info['public_url'])) echo json_encode(['success' => true, 'url' => $info['public_url']]); else echo json_encode(['error' => 'Не удалось получить ссылку']);
            break;
        case 'createYandexRoot':
            $dealId = $input['dealId']; $name = cleanName($input['name']); $fieldCode = $input['fieldCode']; $entityType = $input['entityType'] ?? 'deal';
            if ($entityType === 'lead') {
                $lead = callB24('crm.lead.get', ['id' => $dealId]); $safeTitle = cleanName($lead['TITLE'] ?: 'Lead '.$dealId);
                $suffix = ''; if ($fieldCode === $FIELDS['LEAD_FOLDER_TZ']) $suffix = ' (ТЗ)'; elseif ($fieldCode === $FIELDS['LEAD_FOLDER_OTHER']) $suffix = ' (ИД)';
                $folderName = "[LEAD #{$dealId}] {$safeTitle}{$suffix}"; $fullPath = YA_ROOT . '/' . $folderName; yandexCreatePath($fullPath); callB24('crm.lead.update', ['id' => $dealId, 'fields' => [ $fieldCode => $fullPath ]]);
            } else {
                $deal = callB24('crm.deal.get', ['id' => $dealId]); $safeTitle = cleanName($deal['TITLE'] ?: 'Deal '.$dealId);
                $suffix = ''; if ($fieldCode === $FIELDS['FOLDER_TZ']) $suffix = ' (ТЗ)'; elseif ($fieldCode === $FIELDS['FOLDER_OTHER']) $suffix = ' (ИД)'; elseif ($fieldCode === $FIELDS['FOLDER_RESULTS']) $suffix = ' (Результаты)';
                $folderName = "[#{$dealId}] {$safeTitle}{$suffix}"; $fullPath = YA_ROOT . '/' . $folderName; yandexCreatePath($fullPath); callB24('crm.deal.update', ['id' => $dealId, 'fields' => [ $fieldCode => $fullPath ]]);
            }
            echo json_encode(['success' => true, 'path' => $fullPath]);
            break;
        case 'createYandexFolder':
            $parentPath = $input['path']; $name = cleanName($input['name']); $newPath = $parentPath . '/' . $name; yandexCreatePath($newPath); echo json_encode(['success' => true, 'path' => $newPath]);
            break;
        case 'uploadSimple':
            $path = $input['path']; $fileName = cleanName($input['fileName']); $fileContent = $input['fileContent']; $targetUrl = $path . "/" . $fileName;
            $fp = fopen('php://temp', 'r+'); fwrite($fp, base64_decode($fileContent)); rewind($fp);
            $res = yandexReq('GET', '/upload', ['path' => $targetUrl, 'overwrite' => 'true']); if (empty($res['href'])) { echo json_encode(['error' => 'Yandex Error']); exit; }
            $ch = curl_init($res['href']); curl_setopt($ch, CURLOPT_PUT, true); curl_setopt($ch, CURLOPT_INFILE, $fp); curl_setopt($ch, CURLOPT_INFILESIZE, fstat($fp)['size']); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); if (defined('FAKE_USER_AGENT')) curl_setopt($ch, CURLOPT_USERAGENT, FAKE_USER_AGENT); curl_exec($ch); curl_close($ch); fclose($fp); echo json_encode(['success' => true]);
            break;
        case 'deleteYandexResource':
            $path = $input['path']; if (!$path) { echo json_encode(['error' => 'Путь не указан']); exit; }
            $url = 'https://cloud-api.yandex.net/v1/disk/resources?path=' . urlencode($path) . '&permanently=false';
            $ch = curl_init($url); curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Authorization: OAuth ' . YANDEX_TOKEN ]); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($httpCode === 202 || $httpCode === 204) echo json_encode(['success' => true]); else echo json_encode(['error' => 'Ошибка удаления. Код: ' . $httpCode]);
            break;
        case 'getHistory':
            $tmpHist = callB24('crm.item.list', [
                'entityTypeId' => SP_RESULTS,
                'filter' => [ $FIELDS['RES_LINK_STAGE'] => $input['stageId'] ],
                'select' => ['id', 'title', 'stageId', 'createdTime', $FIELDS['RES_GIP_COMM'], $FIELDS['RES_PRODUCT'], $FIELDS['RES_VERSION'], $FIELDS['RES_COMMENT'], $FIELDS['RES_FOLDER_PATH'], 'createdBy'],
                'order' => ['id' => 'DESC']
            ]);
            $items = ensureArray($tmpHist);
            echo json_encode($items);
            break;
        case 'uploadResultSmart':
            $dealId = $input['dealId']; $productName = $input['product']; $comment = $input['comment'] ?? ''; $passedObjName = $input['objName'] ?? 'Объект';
            $targetStage = 'DT1334_130:PREPARATION'; 
            $isAutoApproved = false;
            if (in_array($userId, ALLOWED_GIP_IDS)) $isAutoApproved = true;
            else { $uData = callB24('user.get', ['ID' => $userId]); if (!empty($uData[0]['WORK_POSITION']) && strpos(mb_strtolower($uData[0]['WORK_POSITION']), 'гип') !== false) $isAutoApproved = true; }
            if ($isAutoApproved) $targetStage = 'DT1334_130:CLIENT';
            $tempDir = $_SERVER['DOCUMENT_ROOT'] . '/upload_temp/'; if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);
            $savedFiles = [];
            if (!empty($_FILES['uploadFiles'])) { $files = $_FILES['uploadFiles']; $count = is_array($files['name']) ? count($files['name']) : 1; for ($i = 0; $i < $count; $i++) { $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name']; $name = is_array($files['name']) ? $files['name'][$i] : $files['name']; if (file_exists($tmpName)) { $localPath = $tempDir . uniqid() . '_' . cleanName($name); if (move_uploaded_file($tmpName, $localPath)) $savedFiles[] = ['path' => $localPath, 'name' => $name]; } } }
            echo json_encode(['success' => true, 'message' => $isAutoApproved ? "Загружено и автоматически согласовано." : "Файлы отправлены на проверку."]);
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request(); else { ignore_user_abort(true); header('Connection: close'); header('Content-Length: ' . ob_get_length()); ob_end_flush(); flush(); }
            set_time_limit(0);
            $deal = callB24('crm.deal.get', ['id' => $dealId]); $rootPath = $deal[$FIELDS['FOLDER_RESULTS']] ?? null;
            if (!$rootPath || strpos($rootPath, '/') !== 0) { $safeTitle = cleanName($deal['TITLE'] ?: 'Deal '.$dealId); $folderName = "[#{$dealId}] {$safeTitle} (Результаты)"; $rootPath = YA_ROOT . '/' . $folderName; callB24('crm.deal.update', ['id' => $dealId, 'fields' => [ $FIELDS['FOLDER_RESULTS'] => $rootPath ]]); }
            $prodPath = $rootPath . '/' . cleanName($productName); $res = yandexReq('GET', '?path=' . urlencode($prodPath) . '&limit=100'); $maxVer = 0;
            if (!isset($res['error']) && isset($res['_embedded']['items'])) { foreach ($res['_embedded']['items'] as $item) { if ($item['type'] === 'dir' && strpos($item['name'], 'Версия ') === 0) { $v = (int)str_replace('Версия ', '', $item['name']); if ($v > $maxVer) $maxVer = $v; } } }
            $newVer = $maxVer + 1; $verName = "Версия $newVer"; $finalPath = $prodPath . '/' . $verName;
            yandexCreatePath($finalPath);
            foreach ($savedFiles as $file) { $targetUrl = $finalPath . "/" . cleanName($file['name']); yandexUploadFromPath($targetUrl, $file['path']); unlink($file['path']); }
            $publicUrl = yandexPublish($finalPath); $finalComment = $comment . "\n\n✅ Загружено: " . $publicUrl;
            if (!empty($input['stageId'])) {
                $newItem = callB24('crm.item.add', [ 'entityTypeId' => SP_RESULTS, 'fields' => [ 'title' => "Результат: {$productName} ({$verName})", 'createdBy' => $userId, $FIELDS['RES_PRODUCT'] => $productName, $FIELDS['RES_VERSION'] => $verName, $FIELDS['RES_COMMENT'] => $finalComment, $FIELDS['RES_LINK_STAGE'] => $input['stageId'], $FIELDS['RES_LINK_SUB'] => $input['subId'], $FIELDS['RES_FOLDER_PATH'] => $finalPath, 'stageId' => $targetStage ] ]);
                if (!$isAutoApproved && defined('CHAT_ID_360') && CHAT_ID_360) { $msgText = "Новая приемка\nПо объекту: \"" . ($deal['TITLE'] ?: $passedObjName) . "\"\nзагружен результат работ\nРабота: \"" . $productName . "\"\nВерсия: \"" . $verName . "\"\n[B]Согласуйте или отклоните[/B]"; callB24('im.message.add', ['DIALOG_ID' => CHAT_ID_360, 'MESSAGE' => $msgText]); }
            }
            break;

        default: echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); }

// --- ФУНКЦИИ (FIXED RETURN) ---
function yandexReq($method, $apiMethod, $params = []) {
    $url = 'https://cloud-api.yandex.net/v1/disk/resources' . $apiMethod;
    if (!empty($params)) $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    $ch = curl_init();
    $headers = [ 'Authorization: OAuth ' . YANDEX_TOKEN, 'Content-Type: application/json' ];
    if (defined('FAKE_USER_AGENT')) $headers[] = 'User-Agent: ' . FAKE_USER_AGENT;
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $res = curl_exec($ch); curl_close($ch);
    return json_decode($res, true);
}
function ensureArray($r) {
    if ($r === null) return [];
    if (is_array($r) && isset($r['error'])) return $r; // keep error through
    if (is_array($r) && isset($r['items']) && is_array($r['items'])) return $r['items'];
    if (is_array($r) && array_values($r) === $r) return $r; // sequential array
    return [];
}
function yandexCreatePath($path) {
    $parts = explode('/', trim($path, '/'));
    $current = '';
    foreach ($parts as $part) { $current .= '/' . $part; yandexReq('PUT', '?path=' . urlencode($current)); }
}
function yandexUploadFromPath($path, $localFilePath) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $throttledTypes = ['rar', 'zip', '7z', 'tar', 'gz', 'mp4', 'avi', 'mov', 'mkv', 'db', 'sql', 'dat'];
    $isRisky = in_array($ext, $throttledTypes);
    $uploadPath = $isRisky ? $path . '.upload_safe' : $path;
    $res = yandexReq('GET', '/upload', ['path' => $uploadPath, 'overwrite' => 'true']);
    if (empty($res['href'])) return;
    $fp = fopen($localFilePath, 'r');
    $ch = curl_init($res['href']);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localFilePath));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    if (defined('FAKE_USER_AGENT')) curl_setopt($ch, CURLOPT_USERAGENT, FAKE_USER_AGENT);
    curl_exec($ch); curl_close($ch); fclose($fp);
    if ($isRisky) yandexReq('POST', '/move', ['from' => $uploadPath, 'path' => $path, 'overwrite' => 'true']);
}
function yandexPublish($path) {
    yandexReq('PUT', '/publish', ['path' => $path]);
    $info = yandexReq('GET', '', ['path' => $path]);
    return $info['public_url'] ?? '';
}
function cleanName($str) { 
    $str = preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ _\-\(\)\.]/u', '_', trim($str));
    if (mb_strlen($str) > 80) $str = mb_substr($str, 0, 80);
    return $str;
}

// ВЕРНУЛ ВОЗВРАТ РЕЗУЛЬТАТА БЕЗ ОБЕРТКИ
function callB24($method, $params) {
    $ch = curl_init(B24_WEBHOOK . $method);
    curl_setopt_array($ch, [ CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => 0 ]);
    $res = json_decode(curl_exec($ch), true); curl_close($ch);
    // ЛОГИРУЕМ ОШИБКИ ТИХО В ФАЙЛ
    if (!is_array($res)) {
        writeLog("B24 API EMPTY/INVALID RESPONSE ({$method}): " . var_export($res, true));
        return ['error' => 'Empty or invalid response from B24'];
    }
    if (isset($res['error'])) {
        writeLog("B24 API ERROR ({$method}): " . ($res['error_description'] ?? $res['error']));
        return ['error' => $res['error_description'] ?? $res['error']];
    }
    // Возвращаем либо результат, либо весь ответ — дальше normalize через ensureArray
    return $res['result'] ?? $res;
}
?>