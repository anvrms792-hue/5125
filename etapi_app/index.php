<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Планирование + Экономика v27 (Paid History Fix)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <style>
        body { background: #f8f9fa; font-size: 12px; padding-bottom: 80px; }
        .main-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 20px; }
        .stats-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 12px; margin-bottom: 15px; height: 100%; }
        .stat-title { color: #6c757d; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .stat-val { font-size: 15px; font-weight: 700; color: #212529; line-height: 1.2; }
        .stat-sub { font-size: 9px; color: #adb5bd; }
        .border-right { border-right: 1px solid #dee2e6; }
        .text-pv { color: #6610f2; }
        .text-risk { color: #dc3545; }
        .text-profit { color: #198754; }
        .grid-header { display: grid; grid-template-columns: 30px 1.4fr 110px 130px 220px 80px 80px 80px 100px 30px 30px; gap: 5px; padding: 8px; background: #f1f3f5; font-weight: 600; border-bottom: 1px solid #dee2e6; color: #495057; font-size: 11px; }
        .grid-row { display: grid; grid-template-columns: 30px 1.4fr 110px 130px 220px 80px 80px 80px 100px 30px 30px; gap: 5px; padding: 6px 8px; border-bottom: 1px solid #f0f0f0; align-items: center; background: #fff; transition: background 0.2s; }
        .form-control-xs, .form-select-xs { font-size: 11px; padding: 2px 4px; height: 24px; }
        input[readonly] { background-color: rgba(0,0,0,0.03) !important; color: #6c757d; }
        .btn-cell { width: 100%; text-align: left; font-size: 10px; padding: 2px 4px; height: 24px; display: flex; align-items: center; justify-content: space-between; }
        .type-stage { border-left: 3px solid #0d6efd; } 
        .type-payment { border-left: 3px solid #198754; }
        .row-overdue:not(.row-done) { background-color: #fff5f5 !important; }
        .row-overdue:not(.row-done) .inp-end { color: #dc3545; font-weight: bold; }
        .row-done { background-color: #e8f5e9 !important; }
        .chk-done { transform: scale(1.1); }
        .tbl-calc th { font-size: 10px; background: #f8f9fa; white-space: nowrap; }
        .tbl-calc td { font-size: 10px; vertical-align: middle; }
        .row-loss { background-color: #fff5f5; }
        .code-formula { font-family: monospace; font-size: 9px; background: #f1f3f5; padding: 1px 3px; border-radius: 3px; display: inline-block; color: #495057; }
        .mini-stat-box { background: #f8f9fa; padding: 6px 8px; border-radius: 6px; height: 100%; }
        .mini-stat-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px; }
        .pen-card { border: 1px solid #dee2e6; border-radius: 6px; padding: 10px; margin-bottom: 15px; background: #fff; }
        .pen-head { font-weight: 700; font-size: 14px; margin-bottom: 5px; }
        
        /* ТОВАРЫ И ПОДЗАДАЧИ */
        .etapi-product-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 12px; background: #fff; margin-bottom: 12px; }
        .etapi-product-card:hover { border-color: #0d6efd; box-shadow: 0 2px 6px rgba(13, 110, 253, 0.1); }
        .product-header-etapi { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; cursor: pointer; user-select: none; }
        .product-title-etapi { font-weight: 600; font-size: 12px; color: #212529; display: flex; align-items: center; gap: 6px; }
        .product-toggle-etapi { color: #6c757d; font-size: 14px; transition: 0.2s; }
        .etapi-product-card.expanded .product-toggle-etapi { transform: rotate(90deg); }
        .product-deadlines-etapi { display: flex; gap: 12px; font-size: 11px; margin-bottom: 8px; }
        .deadline-box-etapi { padding: 6px 10px; background: #f8f9fa; border-radius: 6px; flex: 1; }
        .deadline-label-etapi { font-weight: 600; color: #6c757d; margin-bottom: 2px; }
        .deadline-date-etapi { font-size: 12px; font-weight: 700; color: #0d6efd; }
        .subtasks-container-etapi { display: none; margin-top: 8px; padding-top: 8px; border-top: 1px solid #dee2e6; }
        .etapi-product-card.expanded .subtasks-container-etapi { display: flex; flex-direction: column; gap: 6px; }
        .subtask-item-etapi { display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8f9fa; border-radius: 6px; font-size: 11px; }
        .subtask-checkbox-etapi { width: 16px; height: 16px; cursor: pointer; }
        .subtask-info-etapi { flex: 1; }
        .subtask-title-etapi { font-weight: 500; color: #212529; }
        .subtask-type-etapi { display: inline-block; background: #eef2ff; color: #0d6efd; padding: 1px 4px; border-radius: 3px; margin-top: 2px; font-size: 10px; }
    </style>
</head>
<body>

<div class="container-fluid p-2">
    <!-- TOP PANEL -->
    <div class="row g-2 mb-3 align-items-stretch">
        <!-- 1. СТАТИСТИКА -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stat-title">
                    <span><i class="bi bi-list-check me-1"></i>Статистика</span>
                </div>
                <div class="mini-stat-box">
                    <div class="mini-stat-row fw-bold border-bottom pb-1 mb-1">
                        <span>Всего: <span id="cntTotal">0</span></span>
                        <span class="text-danger">Просроч.: <span id="cntOver">0</span></span>
                    </div>
                    <div class="mini-stat-row"><span>📅 Этапы:</span> <span><span id="cntS">0</span> <span class="text-danger" style="font-size:9px">(<span id="cntOverS">0</span>)</span></span></div>
                    <div class="mini-stat-row"><span>💰 Оплаты:</span> <span><span id="cntP">0</span> <span class="text-danger" style="font-size:9px">(<span id="cntOverP">0</span>)</span></span></div>
                    <div class="mini-stat-row"><span>📌 Иное:</span> <span><span id="cntO">0</span> <span class="text-danger" style="font-size:9px">(<span id="cntOverO">0</span>)</span></span></div>
                </div>
            </div>
        </div>
        <!-- 2. БЮДЖЕТ И ЭКОНОМИКА -->
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stat-title text-primary"><span><i class="bi bi-wallet2 me-1"></i>Бюджет и PV</span></div>
                <div class="row g-2 mb-2 border-bottom pb-2">
                    <div class="col-6 border-right"><div class="stat-val" id="stSumStages">0</div><div class="stat-sub">Этапы (Всего)</div></div>
                    <div class="col-6"><div class="stat-val text-primary" id="stSumStagesClosed">0</div><div class="stat-sub">Этапы (Закрыто)</div></div>
                </div>
                <div class="row g-2 mb-2 border-bottom pb-2">
                    <div class="col-6 border-right"><div class="stat-val" id="stSumPlanPay">0</div><div class="stat-sub">Оплаты (План)</div></div>
                    <div class="col-6"><div class="stat-val text-success" id="stSumFactPay">0</div><div class="stat-sub">Оплаты (Факт)</div></div>
                </div>
                <div class="row g-2">
                    <div class="col-6 border-right"><div class="stat-val text-pv" id="stPV">0</div><div class="stat-sub">Приведенная ст.</div></div>
                    <div class="col-6"><div class="stat-val text-danger" id="stLoss">0</div><div class="stat-sub">Потери <a href="#" onclick="showCalcDetails()" class="text-decoration-none ms-1" style="font-size:9px"><i class="bi bi-info-circle"></i> расчет</a></div></div>
                </div>
            </div>
        </div>
        <!-- 3. НЕУСТОЙКИ -->
        <div class="col-md-5">
            <div class="stats-card position-relative">
                <div class="stat-title text-dark">
                    <span><i class="bi bi-exclamation-triangle me-1"></i>Неустойки</span>
                    <div><button class="btn btn-sm btn-link p-0 me-2 text-decoration-none" style="font-size:10px" onclick="showPenDetails()">(детализация)</button><button class="btn btn-sm btn-outline-secondary py-0" style="font-size:10px" onclick="openPenaltySettings()">⚙️</button></div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="mini-stat-box bg-danger-subtle border border-danger-subtle">
                            <div class="fw-bold text-danger" style="font-size:10px">УГРОЗА (С НАС)</div>
                            <div class="stat-val text-danger mt-1" id="penRisk">0</div>
                            <div class="stat-sub text-danger-emphasis">Просрочка этапов</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mini-stat-box bg-success-subtle border border-success-subtle">
                            <div class="fw-bold text-success" style="font-size:10px">НАЧИСЛИТЬ (НАМ)</div>
                            <div class="stat-val text-success mt-1" id="penProfit">0</div>
                            <div class="stat-sub text-success-emphasis">Просрочка оплат</div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-2"><span class="badge bg-light text-secondary border" style="font-size:9px" id="cbrBadge">ЦБ: Загрузка...</span></div>
            </div>
        </div>
    </div>

    <!-- CONTROLS -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <button class="btn btn-primary btn-sm" onclick="addRow()"><i class="bi bi-plus-lg"></i> Добавить</button>
        <button class="btn btn-success btn-sm" onclick="save()" id="btnSave"><i class="bi bi-save"></i> Сохранить все</button>
    </div>

    <!-- TABLE -->
    <div class="main-card">
        <div class="grid-header">
            <div class="text-center">#</div><div>Наименование</div><div>Тип</div><div>Срок</div><div>Даты</div>
            <div>Связи</div><div>Товары</div><div>Сумма</div><div>Оплачено</div><div></div><div><i class="bi bi-check2-square"></i></div>
        </div>
        <div id="rows"></div>
        <div id="emptyState" class="text-center py-5 text-muted" style="display:none;">
            <i class="bi bi-inbox" style="font-size:2rem; opacity:0.3"></i>
            <p>Нет записей</p>
        </div>
    </div>
    
    <!-- ТОВАРЫ И ПОДЗАДАЧИ -->
    <div style="background:#f8f9fa; padding:20px; margin-top:30px; border-top:2px solid #dee2e6;">
        <h5 style="margin-bottom:16px;"><i class="bi bi-box2"></i> Товары и подзадачи по этапам</h5>
        <div id="productsPanel"></div>
    </div>
</div>

<!-- TEMPLATES & MODALS -->
<div style="display:none">
    <div id="rowTpl" class="grid-row type-stage">
        <div class="text-center"><div class="drag-handle"><i class="bi bi-grip-vertical"></i></div><div class="num text-muted mt-1" style="font-size:9px"></div></div>
        <div><input type="text" class="form-control form-control-xs inp-name"></div>
        <div><select class="form-select form-select-xs inp-type" onchange="setType(this)"><option value="calendar_stage">📅 Этап</option><option value="payment">💰 Оплата</option><option value="other">📌 Иное</option></select></div>
        <div class="d-flex gap-1"><input type="number" class="form-control form-control-xs inp-dur" style="width:40px" value="1" onchange="calcDateJS(this)"><select class="form-select form-select-xs inp-term" style="width:60px" onchange="calcDateJS(this)"><option value="calendar_day">Дн</option><option value="work_day">Раб</option><option value="week">Нед</option><option value="calendar_month">Мес</option></select></div>
        <div class="d-flex gap-1"><input type="date" class="form-control form-control-xs inp-start" onchange="calcDateJS(this)"><input type="date" class="form-control form-control-xs inp-end" readonly></div>
        <div><button class="btn btn-outline-secondary btn-cell" onclick="modLink(this)"><span>Связи</span><span class="badge bg-secondary cnt-link">0</span></button><input type="hidden" class="val-link"></div>
        <div><button class="btn btn-outline-secondary btn-cell btn-prod" onclick="modProd(this)"><span>Товары</span><span class="badge bg-secondary cnt-prod">0</span></button><input type="hidden" class="val-prod" value="[]"></div>
        <div><input type="number" class="form-control form-control-xs inp-sum" placeholder="0.00" onchange="recalcAll()"></div>
        <div class="val-paid ps-1" style="font-size:10px"></div>
        <div class="text-center"><button class="btn btn-link text-danger p-0" onclick="delRow(this)"><i class="bi bi-x-lg"></i></button></div>
        <div class="text-center"><input type="checkbox" class="form-check-input chk-done" onclick="toggleDone(this)"></div>
    </div>
</div>

<div class="modal fade" id="mProd" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header py-2"><h6 class="modal-title">Товары</h6><div class="d-flex gap-2"><button class="btn btn-sm btn-outline-secondary" onclick="refreshProducts()"><i class="bi bi-arrow-clockwise"></i> Обновить</button><button class="btn-close" data-bs-dismiss="modal"></button></div></div><div class="modal-body p-0" id="mProdList" style="max-height:400px;overflow:auto"></div><div class="modal-footer py-1"><button class="btn btn-primary btn-sm" onclick="saveProd()">ОК</button></div></div></div></div>
<div class="modal fade" id="mLink" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header py-2"><h6 class="modal-title">Связи</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0" id="mLinkList" style="max-height:400px;overflow:auto"></div><div class="modal-footer py-1"><button class="btn btn-primary btn-sm" onclick="saveLink()">ОК</button></div></div></div></div>
<div class="modal fade" id="mPenSettings" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header py-2 bg-light"><h6 class="modal-title"><i class="bi bi-gear me-2"></i>Настройка неустоек</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><h6 class="text-danger border-bottom pb-1 mb-2">🔴 Угроза (Мы платим)</h6><div class="row g-2 mb-3"><div class="col-4"><label class="small text-muted">Тип ставки</label><select class="form-select form-select-sm" id="penTypeS"><option value="fixed">Фиксированный %</option><option value="cbr">Ставка ЦБ РФ</option></select></div><div class="col-4"><label class="small text-muted">Размер</label><input type="text" class="form-control form-control-sm" id="penValS" placeholder="0.1 или 1/300"></div><div class="col-4"><label class="small text-muted">База расчета</label><select class="form-select form-select-sm" id="penBaseS"><option value="total">Вся сумма договора</option><option value="item">Сумма этапа</option></select></div></div><h6 class="text-success border-bottom pb-1 mb-2">🟢 Возможность (Нам платят)</h6><div class="row g-2"><div class="col-4"><label class="small text-muted">Тип ставки</label><select class="form-select form-select-sm" id="penTypeP"><option value="fixed">Фиксированный %</option><option value="cbr">Ставка ЦБ РФ</option></select></div><div class="col-4"><label class="small text-muted">Размер</label><input type="text" class="form-control form-control-sm" id="penValP" placeholder="0.1 или 1/300"></div><div class="col-4"><label class="small text-muted">База расчета</label><select class="form-select form-select-sm" id="penBaseP"><option value="total">Вся сумма оплат</option><option value="item">Сумма платежа</option></select></div></div></div><div class="modal-footer py-1 bg-light"><button class="btn btn-primary btn-sm" onclick="savePenaltySettings()">Сохранить</button></div></div></div></div>
<div class="modal fade" id="mCalcDetails" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header py-2 bg-light"><h6 class="modal-title text-primary">Детализация PV</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><table class="table table-sm table-bordered mb-0 tbl-calc"><thead><tr><th>Наименование</th><th class="text-center">План</th><th class="text-center">Факт</th><th>Дней</th><th>Ставка</th><th class="text-end">Номинал</th><th class="text-end">PV</th><th class="text-end text-danger">Потери</th><th>Инфо</th></tr></thead><tbody id="tblCalcBody"></tbody><tfoot class="bg-light fw-bold"><tr><td colspan="5" class="text-end">ИТОГО:</td><td class="text-end" id="clcTotalNom">0</td><td class="text-end" id="clcTotalPV">0</td><td class="text-end text-danger" id="clcTotalLoss">0</td><td></td></tr></tfoot></table></div></div></div></div>
<div class="modal fade" id="mPenDetails" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header py-2 bg-light"><h6 class="modal-title"><i class="bi bi-receipt me-2"></i>Расчет неустоек</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body bg-light" id="penDetailsBody"></div></div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// --- КОНСТАНТЫ СУЩНОСТЕЙ ---
const SP_STAGES = 1290; 
const SP_INCOME = 1310; 
const SP_EXPENSES = 1304; 
const SP_SUBCONTRACTORS = 1266; 
const SP_RATES = 1318;

// --- ПОЛЯ ПРИВЯЗКИ ---
const LINK_STAGE_DEAL = 'ufCrm86_1763852237'; 
const LINK_STAGE_SUB = 'ufCrm86_1764881202';
const LINK_INCOME = 'ufCrm94_1763844594'; 
const LINK_EXPENSE = 'ufCrm92_1763844515';

// --- ПОЛЯ ДАННЫХ ---
const FLD_PAID_INCOME = 'ufCrm94_1763844352'; 
// !!! ИСПРАВЛЕННАЯ КОНСТАНТА ДЛЯ РАСХОДОВ !!!
const FLD_PAID_EXPENSE = 'ufCrm92_1763983424'; 
const FLD_STAGE_STATUS = 'ufCrm86_1763860705';

// --- ПОЛЯ НАСТРОЕК (НЕУСТОЙКИ) ---
const FLD_PEN_RISK_TYPE = 'UF_CRM_1763912787'; 
const FLD_PEN_RISK_VAL = 'UF_CRM_1763912810'; 
const FLD_PEN_RISK_BASE = 'UF_CRM_1763912857';
const FLD_PEN_PROF_TYPE = 'UF_CRM_1763912796'; 
const FLD_PEN_PROF_VAL = 'UF_CRM_1763912817'; 
const FLD_PEN_PROF_BASE = 'UF_CRM_1763912863';

let ctx = {id:0, type:'DEAL'}, cache={p:[], rates:[]}, curRow=null;
let calcLog = [], penLog = []; 
let penConfig = { risk: { type:'fixed', val:'0.1', base:'item' }, profit: { type:'fixed', val:'0.1', base:'item' } };

// --- ИНИЦИАЛИЗАЦИЯ ---
BX24.init(function() {
    let info = BX24.placement.info();
    let opts = info.options || {};
    let placement = info.placement || '';

    ctx.id = opts.ID || 0;
    if(placement.indexOf('DEAL') !== -1) ctx.type = 'DEAL';
    else ctx.type = 'SUBCONTRACTOR';
    
    init();
});

function init() {
    loadProductsJS();
    loadRatesJS();
}

// --- ЗАГРУЗКА ТОВАРОВ (ФИНАЛЬНАЯ РАБОЧАЯ ВЕРСИЯ) ---
function loadProductsJS() {
    if(ctx.type === 'DEAL') {
        BX24.callMethod('crm.deal.productrows.get', { id: ctx.id }, (r) => processProdRes(r));
    } else {
        let hex = (SP_SUBCONTRACTORS).toString(16).toUpperCase(); // 4F2
        BX24.callMethod('crm.productrow.list', {
            filter: { 
                'OWNER_TYPE': 'T' + hex, // T4F2
                'OWNER_ID': ctx.id 
            }
        }, function(res) {
            if(!res.error()) {
                let foundProducts = res.data() || [];
                cache.p = foundProducts.map(p => ({
                    PRODUCT_ID: p.productId || p.product_id || p.PRODUCT_ID || p.id || p.ID,
                    PRODUCT_NAME: p.productName || p.product_name || p.PRODUCT_NAME || p.name || p.NAME || 'Товар'
                }));
            } else {
                console.error("Error loading products:", res.error());
            }
        });
    }
}

function processProdRes(r) {
    if(!r.error()) {
        let raw = r.data();
        cache.p = raw.map(p => ({
            PRODUCT_ID: p.productId || p.product_id || p.PRODUCT_ID || p.id || p.ID,
            PRODUCT_NAME: p.productName || p.product_name || p.PRODUCT_NAME || p.name || p.NAME || 'Товар'
        }));
    }
}

function loadRatesJS() {
    BX24.callMethod('crm.item.list', {
        entityTypeId: SP_RATES,
        select: ['ufCrm98_1763910905', 'ufCrm98_1763910912'],
        order: {'ufCrm98_1763910905': 'ASC'},
        limit: 1000
    }, function(res) {
        if(res.error()) { console.error(res.error()); return; }
        let items = res.data().items;
        cache.rates = items.map(i => ({
            date: i.ufCrm98_1763910905.substr(0,10),
            rate: parseFloat(i.ufCrm98_1763910912.replace(',','.'))
        }));
        
        if(cache.rates.length > 0) {
            let lastR = cache.rates[cache.rates.length-1];
            $('#cbrBadge').text(`ЦБ: ${lastR.rate}% (${new Date(lastR.date).toLocaleDateString('ru')})`);
        } else { 
            $('#cbrBadge').text('ЦБ: Нет данных').addClass('text-danger'); 
        }
        loadDataJS();
    });
}

function refreshProducts() {
    $('#mProdList').html('<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>');
    loadProductsJS();
    setTimeout(() => { modProd(null, true); }, 1500);
}

function loadDataJS() {
    if(ctx.type === 'DEAL') {
        BX24.callMethod('crm.deal.get', { id: ctx.id }, function(r){
            if(r.data()) {
                let d = r.data();
                penConfig.risk = { type: d[FLD_PEN_RISK_TYPE]||'fixed', val: d[FLD_PEN_RISK_VAL]||'0.1', base: d[FLD_PEN_RISK_BASE]||'item' };
                penConfig.profit = { type: d[FLD_PEN_PROF_TYPE]||'fixed', val: d[FLD_PEN_PROF_VAL]||'0.1', base: d[FLD_PEN_PROF_BASE]||'item' };
            }
        });
    }

    let linkFieldStage = (ctx.type === 'DEAL') ? LINK_STAGE_DEAL : LINK_STAGE_SUB;
    let sSel = ['id','title','opportunity', linkFieldStage, FLD_STAGE_STATUS,'ufCrm86_1763846855','ufCrm86_1763846958','ufCrm86_1763847030','ufCrm86_1763846816','ufCrm86_1763846858','ufCrm86_1763846867','ufCrm86_1763846940','ufCrm86_1763846881','ufCrm86_1763845210'];
    
    // ВАЖНО: Массив выбора полей теперь включает правильное поле расходов
    let pSel = ['id','title','opportunity', ctx.type=='DEAL'?LINK_INCOME:LINK_EXPENSE,'ufCrm20_1741452390','ufCrm94_1763847173','ufCrm94_1763847186','ufCrm94_1763847202','ufCrm94_1763847205','ufCrm94_1763847210','ufCrm94_1763847218','ufCrm92_1763847390','ufCrm92_1763847413','ufCrm92_1763847432','ufCrm92_1763847442','ufCrm92_1763847449','ufCrm92_1763847458',FLD_PAID_INCOME,FLD_PAID_EXPENSE];

    let sFilter = {}; sFilter[linkFieldStage] = ctx.id;
    let pFilter = {}; pFilter[ctx.type=='DEAL'?LINK_INCOME:LINK_EXPENSE] = ctx.id;

    BX24.callBatch({
        get_stages: {
            method: 'crm.item.list',
            params: { entityTypeId: SP_STAGES, filter: sFilter, select: sSel }
        },
        get_pays: {
            method: 'crm.item.list',
            params: { entityTypeId: ctx.type=='DEAL'?SP_INCOME:SP_EXPENSES, filter: pFilter, select: pSel }
        }
    }, function(res) {
        let items = [];
        if(res.get_stages.data()) {
            res.get_stages.data().items.forEach(r => {
                let pr=[]; if(r['ufCrm86_1763845210']) r['ufCrm86_1763845210'].split(' || ').forEach(x=>{let y=x.split(':'); if(y.length>1) pr.push({id:y[0],name:y[1]})});
                items.push({
                    id: r.id, isPayment: false, sort: r['ufCrm86_1763846816'], name: r['ufCrm86_1763846855'],
                    eventType: r['ufCrm86_1763846858'], termType: r['ufCrm86_1763846867'], duration: r['ufCrm86_1763846940'],
                    dateStart: (r['ufCrm86_1763846958']||'').substr(0,10), dateEnd: (r['ufCrm86_1763847030']||'').substr(0,10),
                    linkedStages: r['ufCrm86_1763846881'], amount: r.opportunity, products: pr, status: r[FLD_STAGE_STATUS]||''
                });
            });
        }
        if(res.get_pays.data()) {
            res.get_pays.data().items.forEach(r => {
                let pd = ctx.type=='DEAL' ? (r[FLD_PAID_INCOME]||'') : (r[FLD_PAID_EXPENSE]||'');
                let d = { id: r.id, isPayment: true, amount: r.opportunity, products: [], paidData: pd };
                if(ctx.type=='DEAL') Object.assign(d, {
                    sort: r['ufCrm94_1763847173'], name: r['ufCrm94_1763847186'], eventType: 'payment',
                    termType: r['ufCrm94_1763847202'], duration: r['ufCrm94_1763847205'],
                    dateStart: (r['ufCrm94_1763847210']||'').substr(0,10), dateEnd: (r['ufCrm20_1741452390']||'').substr(0,10),
                    linkedStages: r['ufCrm94_1763847218']
                });
                else Object.assign(d, {
                    sort: r['ufCrm92_1763847390'], name: r['ufCrm92_1763847413'], eventType: 'payment',
                    termType: r['ufCrm92_1763847432'], duration: r['ufCrm92_1763847442'],
                    dateStart: (r['ufCrm92_1763847449']||'').substr(0,10), dateEnd: (r['ufCrm20_1741452390']||'').substr(0,10),
                    linkedStages: r['ufCrm92_1763847458']
                });
                items.push(d);
            });
        }
        items.sort((a,b) => (parseInt(a.sort)||0) - (parseInt(b.sort)||0));
        $('#statusBadge').text("Items: " + items.length);
        if(items.length > 0) { $('#emptyState').hide(); items.forEach(item => render(item)); } 
        else { $('#emptyState').show(); render(); }
        nums(); propagateDates(); recalcAll();
    });
    $('#rows').sortable({handle:'.drag-handle', update:nums});
}

function recalcAll() { recalcStats(); calcCounts(); calcPenalties(); }
function calcCounts() {
    let t={all:0, s:0, p:0, o:0}, over={all:0, s:0, p:0, o:0}, sumStages=0, sumStagesClosed=0; 
    let today = new Date(); today.setHours(0,0,0,0);
    $('#rows .grid-row').each(function() {
        t.all++; let type = $(this).find('.inp-type').val(), val = parseFloat($(this).find('.inp-sum').val())||0, isDone = $(this).hasClass('row-done') || $(this).find('.chk-done').is(':checked'); 
        if(type=='calendar_stage') { t.s++; sumStages += val; if(isDone) sumStagesClosed += val; } else if(type=='payment') t.p++; else t.o++;
        let endVal = $(this).find('.inp-end').val();
        if(endVal) { let dEnd = new Date(endVal); if(!isDone && dEnd < today) { over.all++; if(type=='calendar_stage') over.s++; else if(type=='payment') over.p++; else over.o++; } }
    });
    $('#cntTotal').text(t.all); $('#cntOver').text(over.all); $('#cntS').text(t.s); $('#cntOverS').text(over.s); $('#cntP').text(t.p); $('#cntOverP').text(over.p); $('#cntO').text(t.o); $('#cntOverO').text(over.o); $('#stSumStages').text(formatMoney(sumStages)); $('#stSumStagesClosed').text(formatMoney(sumStagesClosed));
}
function calcPenalties() {
    let riskSum = 0, profitSum = 0; penLog = []; let today = new Date(); today.setHours(0,0,0,0);
    let totalStageSum = 0, totalPaySum = 0;
    $('#rows .grid-row').each(function(){ let val = parseFloat($(this).find('.inp-sum').val())||0; let type = $(this).find('.inp-type').val(); if(type == 'calendar_stage') totalStageSum += val; if(type == 'payment') totalPaySum += val; });
    $('#rows .grid-row').each(function() {
        let r = $(this), type = r.find('.inp-type').val(), nm = r.find('.inp-name').val() || 'Без названия', planDateStr = r.find('.inp-end').val();
        if(!planDateStr) return;
        let dPlan = new Date(planDateStr), dFact = null, baseAmount = 0, isPenalty = false, config = null, typeLabel = '';
        if(type === 'payment') {
            let hist = r.data('payHistory') || [], rowSum = parseFloat(r.find('.inp-sum').val())||0, paidSum = 0; hist.forEach(p=>paidSum+=p.amount);
            let debt = rowSum - paidSum;
            if(debt > 0 && today > dPlan) { isPenalty = true; dFact = today; config = penConfig.profit; typeLabel = 'profit'; baseAmount = (config.base === 'total') ? totalPaySum : debt; if(config.base === 'item') baseAmount = debt; }
        } else if (type === 'calendar_stage') {
            let isDone = r.find('.chk-done').is(':checked');
            if(!isDone && today > dPlan) { isPenalty = true; dFact = today; config = penConfig.risk; typeLabel = 'risk'; let rowSum = parseFloat(r.find('.inp-sum').val())||0; baseAmount = (config.base === 'total') ? totalStageSum : rowSum; }
        }
        if(isPenalty && config && dFact) {
            let details = getPenaltyBreakdown(baseAmount, dPlan, dFact, config);
            if(details.total > 0) { if(typeLabel === 'risk') riskSum += details.total; else profitSum += details.total; penLog.push({ name: nm, type: typeLabel, base: baseAmount, start: dPlan, end: dFact, days: details.daysTotal, total: details.total, steps: details.steps, config: config }); }
        }
    });
    $('#penRisk').text(formatMoney(riskSum)); $('#penProfit').text(formatMoney(profitSum));
}
function getPenaltyBreakdown(baseSum, dStart, dEnd, config) {
    let loopDate = new Date(dStart); loopDate.setDate(loopDate.getDate() + 1);
    let steps = [], currentStep = null, grandTotal = 0, totalDays = 0;
    let rateVal = 0; if(config.val.includes('/')) { let parts = config.val.split('/'); rateVal = parseFloat(parts[0]) / parseFloat(parts[1]); } else { rateVal = parseFloat(config.val.replace(',','.')) || 0; }
    while(loopDate <= dEnd) {
        let dateIso = loopDate.toISOString().split('T')[0], keyRate = getRateForDate(dateIso);
        let dailyPenalty = 0, formulaPart = '', rateDisplay = '';
        if(config.type === 'fixed') { dailyPenalty = baseSum * (rateVal / 100); rateDisplay = rateVal + '%'; formulaPart = `${rateVal}%`; } 
        else { dailyPenalty = baseSum * (keyRate / 100) * rateVal; rateDisplay = keyRate.toFixed(2); formulaPart = config.val + ' × ' + keyRate + '%'; }
        if(currentStep && currentStep.rate === rateDisplay) { currentStep.days++; currentStep.sum += dailyPenalty; currentStep.dEnd = new Date(loopDate); } 
        else { if(currentStep) steps.push(currentStep); currentStep = { dStart: new Date(loopDate), dEnd: new Date(loopDate), days: 1, rate: rateDisplay, formula: formulaPart, sum: dailyPenalty }; }
        grandTotal += dailyPenalty; totalDays++; loopDate.setDate(loopDate.getDate() + 1);
    }
    if(currentStep) steps.push(currentStep);
    return { total: grandTotal, daysTotal: totalDays, steps: steps };
}
function showPenDetails() {
    let h = ''; if(penLog.length === 0) h = '<div class="text-center p-4 text-muted">Нет начисленных неустоек</div>';
    else {
        penLog.forEach(item => {
            let colorClass = item.type === 'risk' ? 'text-danger' : 'text-success';
            let typeTitle = item.type === 'risk' ? '🔴 Исходящая претензия (Мы должны)' : '🟢 Входящая претензия (Нам должны)';
            let rows = ''; item.steps.forEach(s => { let p1 = s.dStart.toLocaleDateString('ru'), p2 = s.dEnd.toLocaleDateString('ru'), formulaFull = `${formatMoney(item.base)} × (${s.formula}) × ${s.days}`; rows += `<tr><td>${p1} – ${p2}</td><td class="text-center">${s.days}</td><td class="text-center">${s.rate}</td><td><span class="code-formula">${formulaFull}</span></td><td class="text-end">${formatMoney(s.sum)}</td></tr>`; });
            h += `<div class="pen-card border-start border-4 ${item.type === 'risk'?'border-danger':'border-success'}"><div class="pen-head ${colorClass}">${typeTitle}: ${item.name}</div><div class="mb-2 small">Долг на дату начала (${item.start.toLocaleDateString('ru')}): <b>${formatMoney(item.base)} ₽</b><br>Период просрочки: ${item.start.toLocaleDateString('ru')} – ${item.end.toLocaleDateString('ru')} (${item.days} дней)</div><table class="table table-sm table-bordered mb-1 tbl-calc"><thead><tr><th>Период</th><th width="40">Дней</th><th width="60">Ставка</th><th>Формула</th><th width="100" class="text-end">Сумма</th></tr></thead><tbody>${rows}</tbody><tfoot class="fw-bold"><tr><td colspan="4" class="text-end">ИТОГО:</td><td class="text-end">${formatMoney(item.total)}</td></tr></tfoot></table></div>`;
        });
    }
    $('#penDetailsBody').html(h); new bootstrap.Modal('#mPenDetails').show();
}
function openPenaltySettings() { $('#penTypeS').val(penConfig.risk.type); $('#penValS').val(penConfig.risk.val); $('#penBaseS').val(penConfig.risk.base); $('#penTypeP').val(penConfig.profit.type); $('#penValP').val(penConfig.profit.val); $('#penBaseP').val(penConfig.profit.base); new bootstrap.Modal('#mPenSettings').show(); }
function savePenaltySettings() { penConfig.risk = { type: $('#penTypeS').val(), val: $('#penValS').val(), base: $('#penBaseS').val() }; penConfig.profit = { type: $('#penTypeP').val(), val: $('#penValP').val(), base: $('#penBaseP').val() }; 
    if(ctx.type === 'DEAL') {
        let f = {}; f[FLD_PEN_RISK_TYPE] = penConfig.risk.type; f[FLD_PEN_RISK_VAL] = penConfig.risk.val; f[FLD_PEN_RISK_BASE] = penConfig.risk.base; f[FLD_PEN_PROF_TYPE] = penConfig.profit.type; f[FLD_PEN_PROF_VAL] = penConfig.profit.val; f[FLD_PEN_PROF_BASE] = penConfig.profit.base;
        BX24.callMethod('crm.deal.update', {id: ctx.id, fields: f});
    }
    bootstrap.Modal.getInstance('#mPenSettings').hide(); recalcAll();
}

// --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---
function addTime(d, dur, type) { if(!d) return ''; let date = new Date(d); let v = parseInt(dur)||0; if(type=='calendar_month') date.setMonth(date.getMonth()+v); else if(type=='week') date.setDate(date.getDate()+(v*7)); else if(type=='work_day') { let a=0; while(a<v){ date.setDate(date.getDate()+1); if(date.getDay()!=0&&date.getDay()!=6) a++; } } else date.setDate(date.getDate()+v); return date.toISOString().split('T')[0]; }
function calcDateJS(el) { let r=$(el).closest('.grid-row'), s=r.find('.inp-start').val(), d=r.find('.inp-dur').val(), t=r.find('.inp-term').val(); if(s) r.find('.inp-end').val(addTime(s, d, t)); propagateDates(); }
function propagateDates() { let map={}; $('#rows .grid-row').each(function(){ let id=$(this).data('id').toString(); map[id]=$(this); map[($(this).find('.inp-type').val()=='payment'?(ctx.type=='DEAL'?1310:1304):1290)+'-'+id]=$(this); }); let ch=true, lim=10; while(ch && lim>0) { ch=false; lim--; $('#rows .grid-row').each(function(){ let r=$(this), lnk=r.find('.val-link').val(); if(!lnk) return; let max=null; lnk.split('/').forEach(l=>{ if(!l)return; let p=map[l]||(!l.includes('-')?map[l]:null); if(p){ let e=p.find('.inp-end').val(); if(e && (!max || e>max)) max=e; } }); if(max){ let s=r.find('.inp-start').val(); if(s!==max){ r.find('.inp-start').val(max); r.find('.inp-end').val(addTime(max, r.find('.inp-dur').val(), r.find('.inp-term').val())); ch=true; } } }); } recalcAll(); checkOverdue(); }
function checkOverdue() { let now=new Date(); now.setHours(0,0,0,0); $('#rows .grid-row').each(function(){ let r=$(this); if(!r.hasClass('row-done')){ r.removeClass('row-overdue'); let e=r.find('.inp-end').val(); if(e && new Date(e)<now) r.addClass('row-overdue'); } }); }
function render(d=null) { let r=$('#rowTpl').clone().removeAttr('id'); r.attr('data-id', d?d.id:'new_'+Date.now()); let isP=false, isC=false; if(d) { isP=(d.eventType=='payment'); r.find('.inp-name').val(d.name); r.find('.inp-type').val(d.eventType||'calendar_stage'); r.find('.inp-dur').val(d.duration||1); r.find('.inp-term').val(d.termType||'calendar_day'); r.find('.inp-start').val(d.dateStart); r.find('.inp-end').val(d.dateEnd); r.find('.val-link').val(d.linkedStages); r.find('.cnt-link').text(d.linkedStages?d.linkedStages.split('/').length:0); r.find('.inp-sum').val(d.amount); r.find('.val-prod').val(JSON.stringify(d.products||[])); r.find('.cnt-prod').text((d.products||[]).length); setType(r.find('.inp-type')); if(isP) { let pS=d.paidData||'', tot=0, h='', pObj=[]; if(pS) pS.split('/').forEach((v,i,a)=>{ if(i%2===0){ let n=parseFloat(v.replace(/\s/g,'').replace(',','.'))||0, dt=a[i+1]||''; if(n>0){ tot+=n; pObj.push({date:dt, amount:n}); h+=`<div>${dt} <b>${n.toLocaleString('ru')}</b></div>`; } } }); r.data('payHistory', pObj); r.find('.val-paid').html(tot>0?`<div class="paid-list">${h}<div class="paid-total">${tot.toLocaleString('ru')}</div></div>`:'--'); if((parseFloat(d.amount)||0)>0 && tot>=parseFloat(d.amount)) isC=true; r.find('.chk-done').parent().html(''); } else { if(d.status=='Закрыт') { isC=true; r.find('.chk-done').prop('checked',true); } } if(isC) { r.addClass('row-done'); disableRow(r,true); } } else { r.find('.inp-start').val(new Date().toISOString().split('T')[0]); calcDateJS(r.find('.inp-start')); } $('#rows').append(r); }
function recalcStats() { let plan=0, fact=0, pv=0, today=new Date(); today.setHours(0,0,0,0); calcLog=[]; $('#rows .grid-row').each(function(){ let r=$(this), type=r.find('.inp-type').val(), am=parseFloat(r.find('.inp-sum').val())||0, nm=r.find('.inp-name').val(); if(type=='payment') { plan+=am; let hist=r.data('payHistory')||[], end=r.find('.inp-end').val(), pd=0; hist.forEach(p=>{ pd+=p.amount; fact+=p.amount; let res={pv:p.amount, days:0, avgRate:0}, cmt="Вовремя"; if(end && p.date){ let dp=p.date.split('.'), df=`${dp[2]}-${dp[1]}-${dp[0]}`; res=calculatePV(p.amount, end, df); if(res.pv<p.amount) cmt="Задержка"; } pv+=res.pv; calcLog.push({name:nm, dP:end, dF:p.date, nom:p.amount, pv:res.pv, loss:p.amount-res.pv, d:res.days, r:res.avgRate, c:cmt}); }); let debt=am-pd; if(debt>0) { let res={pv:debt, days:0, avgRate:0}, cmt="Ожидание", df=''; if(end && new Date(end)<today){ res=calculatePV(debt, end, today.toISOString().split('T')[0]); cmt="Просрочка"; df=today.toLocaleDateString('ru'); } pv+=res.pv; calcLog.push({name:nm+' (Долг)', dP:end, dF:df, nom:debt, pv:res.pv, loss:debt-res.pv, d:res.days, r:res.avgRate, c:cmt}); } } }); $('#stSumPlanPay').text(formatMoney(plan)); $('#stSumFactPay').text(formatMoney(fact)); $('#stPV').text(formatMoney(pv)); let ls=plan-pv; if(ls<0)ls=0; $('#stLoss').text(formatMoney(ls)); }
function calculatePV(am, dP, dF) { let dp=new Date(dP), df=new Date(dF); if(df<=dp) return {pv:am, days:0, avgRate:0}; let cur=am, loop=new Date(dp); loop.setDate(loop.getDate()+1); let d=0, sr=0; while(loop<=df) { let r=getRateForDate(loop.toISOString().split('T')[0]); cur=cur/(1+(r/100/365)); sr+=r; d++; loop.setDate(loop.getDate()+1); } return {pv:cur, days:d, avgRate:d>0?sr/d:0}; }
function getRateForDate(s) { let r=15; if(cache.rates.length){ r=cache.rates[0].rate; for(let i=0;i<cache.rates.length;i++) if(cache.rates[i].date<=s) r=cache.rates[i].rate; else break; } return r; }
function formatMoney(v) { return v.toLocaleString('ru',{maximumFractionDigits:2, minimumFractionDigits:2}); }
function toggleDone(c) { let r=$(c).closest('.grid-row'); if($(c).is(':checked')) { r.addClass('row-done').removeClass('row-overdue'); disableRow(r,1); } else { r.removeClass('row-done'); disableRow(r,0); checkOverdue(); } recalcAll(); }
function disableRow(r,s) { r.find('input,select,button').not('.chk-done').prop('disabled',s); r.find('.drag-handle').css('opacity',s?0.3:1); }
function addRow() { $('#emptyState').hide(); render(); nums(); recalcAll(); }
function delRow(b) { 
    if(!confirm('Точно удалить?')) return;
    let r = $(b).closest('.grid-row');
    let id = r.data('id');
    
    if(id && !String(id).startsWith('new_')) {
        let type = r.find('.inp-type').val();
        let entityType = SP_STAGES;
        if(type === 'payment') entityType = (ctx.type === 'DEAL' ? SP_INCOME : SP_EXPENSES);
        
        BX24.callMethod('crm.item.delete', { entityTypeId: entityType, id: id }, function(res) {
            if(!res.error()) {
                r.remove(); nums(); recalcAll();
            } else alert('Ошибка удаления: ' + res.error());
        });
    } else {
        r.remove(); nums(); recalcAll();
    }
}
function nums() { $('#rows .grid-row').each(function(i){ $(this).find('.num').text(i+1); }); }
function setType(el) { let r=$(el).closest('.grid-row'), t=$(el).val(); r.removeClass('type-stage type-payment').addClass(t=='payment'?'type-payment':'type-stage'); r.find('.btn-prod').prop('disabled',t=='payment'); if(t=='payment'){r.find('.chk-done').parent().html('');r.find('.inp-sum').attr('placeholder','Сумма');} else {if(!r.find('.chk-done').length) r.find('.text-center:last').html('<input type="checkbox" class="form-check-input chk-done" onclick="toggleDone(this)">'); r.find('.inp-sum').attr('placeholder','Стоим.');} recalcAll(); }
function modLink(b) { curRow=$(b).closest('.grid-row'); let id=curRow.data('id'), h='', cur=curRow.find('.val-link').val()||''; $('#rows .grid-row').each(function(){ let i=$(this).data('id'); if(i!=id) h+=`<label class="d-flex p-1 border-bottom"><input type="checkbox" class="me-2 chk-l" value="${$(this).find('.inp-type').val()=='payment'?(ctx.type=='DEAL'?1310:1304):1290}-${i}" ${cur.includes(i)?'checked':''}>${$(this).find('.inp-name').val()}</label>`; }); $('#mLinkList').html(h||'Нет элементов'); new bootstrap.Modal('#mLink').show(); }
function saveLink() { let i=[]; $('#mLinkList .chk-l:checked').each(function(){i.push($(this).val())}); curRow.find('.val-link').val(i.join('/')); curRow.find('.cnt-link').text(i.length); calcDateJS(curRow.find('.inp-start')); bootstrap.Modal.getInstance('#mLink').hide(); }
function modProd(b, refresh) { 
    curRow = b ? $(b).closest('.grid-row') : curRow; 
    let cur=JSON.parse(curRow.find('.val-prod').val()||'[]').map(x=>x.id), h=''; 
    if(cache.p.length===0) h='<div class="p-3 text-muted text-center">Товары не найдены</div>'; 
    else cache.p.forEach(p=>{ 
        let i=(p.PRODUCT_ID||p.ID).toString(); 
        let n = p.PRODUCT_NAME || p.NAME || 'Товар';
        h+=`<label class="d-flex p-1 border-bottom"><input type="checkbox" class="me-2 chk-p" value="${i}" data-n="${n}" ${cur.includes(i)?'checked':''}>${n}</label>`; 
    }); 
    $('#mProdList').html(h); if(!refresh) new bootstrap.Modal('#mProd').show(); 
}
function saveProd() { let r=[]; $('#mProdList .chk-p:checked').each(function(){r.push({id:$(this).val(),name:$(this).data('n')})}); curRow.find('.val-prod').val(JSON.stringify(r)); curRow.find('.cnt-prod').text(r.length); bootstrap.Modal.getInstance('#mProd').hide(); }
function showCalcDetails() { let h=''; calcLog.forEach(r=>{ h+=`<tr class="${r.loss>0?'row-loss':''}"><td>${r.name}</td><td class="text-center">${r.dP||'-'}</td><td class="text-center">${r.dF||'-'}</td><td class="text-center">${r.d}</td><td class="text-center">${r.r.toFixed(2)}%</td><td class="text-end">${formatMoney(r.nom)}</td><td class="text-end">${formatMoney(r.pv)}</td><td class="text-end text-danger">${r.loss>0?formatMoney(r.loss):'-'}</td><td class="small">${r.c}</td></tr>`; }); $('#tblCalcBody').html(h); new bootstrap.Modal('#mCalcDetails').show(); }

// --- СОХРАНЕНИЕ ---
function save() {
    let batch = {};
    $('#rows .grid-row').each(function(i) {
        let r=$(this), isP=r.find('.inp-type').val()=='payment';
        let id = r.data('id');
        let isNew = String(id).startsWith('new_');
        let f = {};
        
        f['title'] = r.find('.inp-name').val();
        f['opportunity'] = r.find('.inp-sum').val();
        
        let typeID = 0;
        
        if(isP) {
            typeID = ctx.type=='DEAL' ? SP_INCOME : SP_EXPENSES;
            if(ctx.type=='DEAL') {
                f[LINK_INCOME] = ctx.id;
                f['ufCrm94_1763847194']='Оплата'; 
                f['ufCrm94_1763847173'] = r.find('.num').text();
                f['ufCrm94_1763847186'] = r.find('.inp-name').val();
                f['ufCrm94_1763847202'] = r.find('.inp-term').val();
                f['ufCrm94_1763847205'] = r.find('.inp-dur').val();
                f['ufCrm94_1763847210'] = r.find('.inp-start').val();
                f['ufCrm20_1741452390'] = r.find('.inp-end').val();
                f['ufCrm94_1763847218'] = r.find('.val-link').val();
            } else {
                f[LINK_EXPENSE] = ctx.id;
                f['ufCrm92_1763847427']='Оплата';
                f['ufCrm92_1763847390'] = r.find('.num').text();
                f['ufCrm92_1763847413'] = r.find('.inp-name').val();
                f['ufCrm92_1763847432'] = r.find('.inp-term').val();
                f['ufCrm92_1763847442'] = r.find('.inp-dur').val();
                f['ufCrm92_1763847449'] = r.find('.inp-start').val();
                f['ufCrm20_1741452390'] = r.find('.inp-end').val();
                f['ufCrm92_1763847458'] = r.find('.val-link').val();
            }
        } else {
            typeID = SP_STAGES;
            f['ufCrm86_1763846858'] = r.find('.inp-type').val();
            if(ctx.type === 'DEAL') { f[LINK_STAGE_DEAL] = ctx.id; f[LINK_STAGE_SUB] = ''; } 
            else { f[LINK_STAGE_SUB] = ctx.id; f[LINK_STAGE_DEAL] = ''; }
            
            f['ufCrm86_1763846816'] = r.find('.num').text();
            f['ufCrm86_1763846855'] = r.find('.inp-name').val();
            f['ufCrm86_1763846867'] = r.find('.inp-term').val();
            f['ufCrm86_1763846940'] = r.find('.inp-dur').val();
            f['ufCrm86_1763846958'] = r.find('.inp-start').val();
            f['ufCrm86_1763847030'] = r.find('.inp-end').val();
            f['ufCrm86_1763846881'] = r.find('.val-link').val();
            f[FLD_STAGE_STATUS] = r.find('.chk-done').is(':checked') ? 'Закрыт' : '';
            
            let prods = JSON.parse(r.find('.val-prod').val()||'[]');
            f['ufCrm86_1763845210'] = prods.length ? prods.map(p=>p.id+':'+p.name).join(' || ') : '';
        }

        let cmd = isNew ? 'crm.item.add' : 'crm.item.update';
        let params = { entityTypeId: typeID, fields: f };
        if(!isNew) params.id = id;
        
        batch['cmd_'+i] = { method: cmd, params: params };
    });

    $('#btnSave').prop('disabled', true);
    BX24.callBatch(batch, function(res) {
        $('#btnSave').prop('disabled', false);
        alert('Сохранено');
        location.reload();
    });
}

// ===== ТОВАРЫ И ПОДЗАДАЧИ =====
function loadStageProductsEtapi() {
    const panel = document.getElementById('productsPanel');
    panel.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Загрузка...</div>';
    
    // Собираем ID всех этапов
    const stageIds = [];
    document.querySelectorAll('.grid-row[data-id]').forEach(row => {
        const type = row.querySelector('.inp-type').value;
        if (type === 'calendar_stage') {
            const id = row.getAttribute('data-id');
            if (id && !id.startsWith('new_')) {
                stageIds.push(parseInt(id));
            }
        }
    });
    
    if (!stageIds.length) {
        panel.innerHTML = '<p style="color:#999">Нет этапов</p>';
        return;
    }
    
    // Загружаем товары для каждого этапа
    BX24.callMethod('crm.item.list', {
        entityTypeId: 1342,  // SP_STAGE_PRODUCTS
        filter: { '@ufCrm110_1765572811': stageIds },
        select: [
            'id', 'title',
            'ufCrm110_1765572838',  // LINK_PRODUCT
            'ufCrm110_1765572860',  // DEADLINE_JURE
            'ufCrm110_1765572878',  // STATUS_JURE
            'ufCrm110_1765572889',  // DEADLINE_FACTO
            'ufCrm110_1765572898'   // STATUS_FACTO
        ]
    }, function(res) {
        const products = res.data().items || [];
        
        if (!products.length) {
            panel.innerHTML = '<p style="color:#999">Нет товаров</p>';
            return;
        }
        
        // Загружаем подзадачи для всех товаров
        const productIds = products.map(p => p.id);
        BX24.callMethod('crm.item.list', {
            entityTypeId: 1346,  // SP_SUBTASKS
            filter: { '@ufCrm112_1765572991': productIds },
            select: [
                'id', 'title',
                'ufCrm112_1765573009',  // TASK_TYPE
                'ufCrm112_1765573037',  // STATUS
                'ufCrm112_1765573044',  // PRIORITY
                'ufCrm112_1765573073'   // DUE_DATE
            ]
        }, function(resSubtasks) {
            const subtasks = resSubtasks.data().items || [];
            const subtasksByProduct = {};
            subtasks.forEach(st => {
                const prodId = st.ufCrm112_1765572991;
                if (!subtasksByProduct[prodId]) subtasksByProduct[prodId] = [];
                subtasksByProduct[prodId].push(st);
            });
            
            panel.innerHTML = products.map(prod => `
                <div class="etapi-product-card" data-product-id="${prod.id}">
                    <div class="product-header-etapi" onclick="toggleProductEtapi(this.closest('.etapi-product-card'))">
                        <div class="product-title-etapi">
                            <span class="product-toggle-etapi">▸</span>
                            <span>${prod.title || 'Товар'}</span>
                        </div>
                        <span style="font-size:11px; color:#6c757d;">${(subtasksByProduct[prod.id] || []).length} подзадач</span>
                    </div>
                    <div class="product-deadlines-etapi">
                        <div class="deadline-box-etapi">
                            <div class="deadline-label-etapi">Де-Юре</div>
                            <div class="deadline-date-etapi">${formatDate(prod.ufCrm110_1765572860)}</div>
                        </div>
                        <div class="deadline-box-etapi">
                            <div class="deadline-label-etapi">Де-Факто</div>
                            <div class="deadline-date-etapi">${formatDate(prod.ufCrm110_1765572889)}</div>
                        </div>
                    </div>
                    <div class="subtasks-container-etapi">
                        ${(subtasksByProduct[prod.id] || []).map(st => `
                            <div class="subtask-item-etapi">
                                <input type="checkbox" class="subtask-checkbox-etapi">
                                <div class="subtask-info-etapi">
                                    <div class="subtask-title-etapi">${st.title}</div>
                                    <div class="subtask-type-etapi">${st.ufCrm112_1765573009 || 'Прочее'}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');
        });
    });
}

function toggleProductEtapi(el) {
    el.classList.toggle('expanded');
}

function formatDate(s) { 
    try { return s ? new Date(s).toLocaleDateString() : '-'; } 
    catch(e) { return s; } 
}

// Загружаем товары при инициализации
setTimeout(() => { loadStageProductsEtapi(); }, 1000);
</script>
</body>
</html>