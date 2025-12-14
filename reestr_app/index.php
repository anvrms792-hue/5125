<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реестр Документации (FINAL v4)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
    /* === ОСНОВНЫЕ СТИЛИ === */
    body { font-family: 'Segoe UI', system-ui, sans-serif; font-size: 13px; background: #f8f9fa; padding: 15px; margin: 0; }
    * { box-sizing: border-box; }
    
    .panel { background: #fff; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px; display: flex; gap: 10px; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); flex-wrap: wrap; }
    .table-container { border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; overflow-x: auto; padding-bottom: 60px; }
    .main-table { width: 100%; border-collapse: collapse; min-width: 1400px; }
    .main-table th { background: #f8fafc; padding: 12px 15px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600; color: #64748b; font-size: 11px; text-transform: uppercase; white-space: nowrap; }
    .main-table td { padding: 10px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: top; height: auto; }
    .main-table tr:hover td { background: #f1f5f9; }
    
    /* Логистика */
    .log-wrapper { display: block; width: 100%; padding-top: 4px; }
    .log-item { display: flex; flex-direction: column; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 6px; padding: 6px 8px; width: 100%; transition: 0.2s; }
    .log-item.active { background: #f8fafc; border-color: #cbd5e1; }
    .log-item.dragover { background: #e0f2fe; border-color: #3b82f6; border-style: dashed; }
    .log-row-main { display: flex; align-items: center; justify-content: space-between; width: 100%; height: 28px; }
    .log-row-files { margin-top: 8px; padding-top: 6px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
    .log-left { display: flex; align-items: center; gap: 8px; font-weight: 500; color: #334155; width: 150px; flex-shrink: 0; }
    .log-right { display: flex; align-items: center; gap: 6px; }
    
    .inp { border: 1px solid #cbd5e1; border-radius: 3px; padding: 0 6px; font-size: 12px; height: 26px; color: #334155; background: #fff; }
    .inp:focus { border-color: #3b82f6; outline: none; }
    
    .btn-action { padding: 6px 16px; border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; cursor: pointer; transition: 0.2s; font-weight: 500; font-size: 13px; color: #334155; }
    .btn-action:hover { background: #f1f5f9; border-color: #94a3b8; }
    .btn-primary-custom { background: #3b82f6; color: white; border-color: #3b82f6; }
    .btn-primary-custom:hover { background: #2563eb; color: white; }

    /* Файлы */
    .file-icon { font-size: 18px; cursor: pointer; margin-right: 8px; text-decoration: none; transition: 0.2s; }
    .icon-pdf { color: #ef4444; } 
    .icon-doc { color: #2563eb; } 
    .upload-btn { cursor: pointer; color: #64748b; font-size: 18px; transition: 0.2s; display: flex; align-items: center; }
    .upload-btn:hover { color: #3b82f6; }
    .conf-file { display: flex; align-items: center; background: #fff; border: 1px solid #cbd5e1; padding: 2px 6px; border-radius: 4px; font-size: 11px; color: #334155; text-decoration: none; }
    
    .link-entity { color: #2563eb; text-decoration: none; border-bottom: 1px dashed transparent; cursor: pointer; font-weight: 500; display: inline-block; margin-right: 5px; }
    .link-entity:hover { border-bottom-color: #2563eb; }
    .doc-mini-card { margin-bottom: 6px; line-height: 1.3; }
    .type-badge { background:#f1f5f9; padding:2px 6px; border-radius:4px; border:1px solid #cbd5e1; font-size:11px; display: inline-block; margin-bottom: 2px; }
    
    #loader { padding: 50px; text-align: center; color: #64748b; font-size: 14px; }
    .pagination { margin-top: 15px; display: flex; justify-content: center; gap: 20px; align-items: center; padding-bottom: 30px; }

    /* === МОДАЛЬНОЕ ОКНО === */
    .modal-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.5); z-index: 1000; 
        display: none; align-items: flex-start; justify-content: center; 
        backdrop-filter: blur(2px); padding-top: 50px; 
    }
    .modal-box { background: #fff; width: 600px; max-width: 90%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column; overflow: visible; animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 12px 12px 0 0; }
    .modal-title { font-size: 16px; font-weight: 600; color: #334155; }
    .modal-body { padding: 20px; max-height: 80vh; overflow-y: auto; overflow-x: visible; }
    .modal-footer { padding: 15px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; background: #f8fafc; border-radius: 0 0 12px 12px; }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; font-weight: 500; color: #475569; margin-bottom: 5px; font-size: 12px; }
    .form-control-custom { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; transition: 0.2s; }
    .form-control-custom:focus { border-color: #3b82f6; outline: none; }
    
    /* АВТОКОМПЛИТ */
    .ac-wrapper { position: relative; }
    .ac-list { position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #cbd5e1; border-top: none; z-index: 1050; max-height: 200px; overflow-y: auto; display: none; border-radius: 0 0 6px 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .ac-item { padding: 8px 10px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
    .ac-item:hover { background: #f8fafc; color: #2563eb; }
    .ac-clear { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; display: none; }
    .ac-input { padding-right: 30px; }
    
    .edit-btn { color: #f59e0b; cursor: pointer; font-size: 16px; padding: 4px; border-radius: 4px; transition: 0.2s; }
    .edit-btn:hover { background: #fef3c7; }

    #existing-files-block { margin-bottom: 10px; display: none; }
    .existing-file-tag { display: inline-flex; align-items: center; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-right: 5px; margin-bottom: 5px; }
</style>
</head>
<body>

<div class="panel">
    <input type="text" id="search" class="form-control form-control-sm" style="padding:6px; border:1px solid #cbd5e1; border-radius:4px; width:200px;" placeholder="Поиск (Название/Номер)...">
    <select id="filter-company" class="form-select form-select-sm" style="width: 200px; border-color: #cbd5e1;" onchange="app.reset()"><option value="">Все компании</option></select>
    <select id="filter-type" class="form-select form-select-sm" style="width: 200px; border-color: #cbd5e1;" onchange="app.reset()"><option value="">Все типы документов</option></select>
    <button class="btn-action" onclick="app.reset()">Найти</button>
    <div style="flex-grow:1"></div>
    <button class="btn-action btn-primary-custom" onclick="app.openModal()">
        <i class="bi bi-plus-lg"></i> Создать
    </button>
</div>

<div id="loader">
    <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
    <div style="margin-top:10px">Загрузка данных...</div>
</div>

<div class="table-container">
    <table class="main-table">
        <thead>
            <tr>
                <th width="4%"></th>
                <th width="12%">Компания</th>
                <th width="8%">Тип</th>
                <th width="15%">Документ</th>
                <th width="8%">Файлы</th> 
                <th width="15%">Связ. док.</th>
                <th width="10%">Сделка</th>
                <th style="min-width: 450px;">Логистика</th>
            </tr>
        </thead>
        <tbody id="tbody"></tbody>
    </table>
</div>

<div class="pagination">
    <button class="btn-action" id="btn-prev" onclick="app.prev()" disabled>← Назад</button>
    <span id="page-num" style="color:#64748b; font-weight:500;">Страница 1</span>
    <button class="btn-action" id="btn-next" onclick="app.next()" disabled>Вперед →</button>
</div>

<!-- === МОДАЛКА (CREATE / EDIT) === -->
<div class="modal-overlay" id="create-modal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="modal-title-text">Регистрация документа</div>
            <button type="button" class="btn-close" onclick="app.closeModal()"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-id"> 
            
            <div class="form-group">
                <label class="form-label">Тема письма / Название <span style="color:red">*</span></label>
                <input type="text" id="new-name" class="form-control-custom">
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Наша компания</label>
                        <select id="new-company" class="form-control-custom"><option value="">Выбрать...</option></select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Тип документа</label>
                        <select id="new-type" class="form-control-custom"><option value="">Выбрать...</option></select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Номер документа</label>
                        <input type="text" id="new-num" class="form-control-custom">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Дата документа</label>
                        <input type="date" id="new-date" class="form-control-custom">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Адресат (Компания из CRM)</label>
                <div class="ac-wrapper">
                    <input type="text" id="search-company" class="form-control-custom ac-input" placeholder="Начните вводить..." autocomplete="off" onkeyup="app.onSearchInput('company', this)">
                    <i class="bi bi-x-circle-fill ac-clear" id="clear-company" onclick="app.clearSearch('company')"></i>
                    <input type="hidden" id="new-addr-id">
                    <div class="ac-list" id="res-company"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Привязка к сделке</label>
                <div class="ac-wrapper">
                    <input type="text" id="search-deal" class="form-control-custom ac-input" placeholder="Начните вводить..." autocomplete="off" onkeyup="app.onSearchInput('deal', this)">
                    <i class="bi bi-x-circle-fill ac-clear" id="clear-deal" onclick="app.clearSearch('deal')"></i>
                    <input type="hidden" id="new-deal-id">
                    <div class="ac-list" id="res-deal"></div>
                </div>
            </div>

            <!-- НОВОЕ: СВЯЗАННЫЙ ДОКУМЕНТ -->
            <div class="form-group">
                <label class="form-label">Связанный документ (из реестра)</label>
                <div class="ac-wrapper">
                    <input type="text" id="search-linked" class="form-control-custom ac-input" placeholder="Начните вводить название документа..." autocomplete="off" onkeyup="app.onSearchInput('linked', this)">
                    <i class="bi bi-x-circle-fill ac-clear" id="clear-linked" onclick="app.clearSearch('linked')"></i>
                    <input type="hidden" id="new-linked-id">
                    <div class="ac-list" id="res-linked"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Файлы</label>
                <div id="existing-files-block">
                    <small style="color:#64748b">Уже загружено: </small>
                    <div id="existing-files-list" style="margin-top:5px;"></div>
                </div>
                <input type="file" id="new-files" class="form-control-custom" multiple>
                <small style="color:#94a3b8">Новые файлы будут добавлены к существующим</small>
            </div>
        </div>
        <div class="modal-footer">
            <div id="create-spinner" class="spinner-border spinner-border-sm text-primary" style="display:none; margin-right:10px;"></div>
            <button class="btn-action" onclick="app.closeModal()">Отмена</button>
            <button class="btn-action btn-primary-custom" onclick="app.saveForm()">Сохранить</button>
        </div>
    </div>
</div>

<script>
    const MANUAL_STAGES = {
        82: [ { id: 'NEW', name: 'Подготовка к отправке' }, { id: 'PREPARATION', name: 'Распечатано' }, { id: 'CLIENT', name: 'На подписании' }, { id: 'UC_U76HSB', name: 'Передано для отправки' }, { id: 'UC_AOX0VS', name: 'Отправлено' }, { id: 'UC_5WUSP1', name: 'Отслеживание получения' }, { id: 'UC_SF3EVK', name: 'Ожидает обратного направления' }, { id: 'UC_YD74B2', name: 'Возвращено без получения' }, { id: 'SUCCESS', name: 'Направлено-получено' }, { id: 'UC_1HOBTM', name: 'Уничтожено' } ],
        84: [ { id: 'NEW', name: 'Подготовка к отправке' }, { id: 'PREPARATION', name: 'Ожидает обратного отправления' }, { id: 'SUCCESS', name: 'Направлено-получено' } ],
        86: [ { id: 'NEW', name: 'Подготовка к отправке' }, { id: 'PREPARATION', name: 'Направлено' }, { id: 'CLIENT', name: 'Ожидает подписания' }, { id: 'SUCCESS', name: 'Направлено-получено' } ],
        88: [ { id: 'NEW', name: 'Подготовка к отправке (печать)' }, { id: 'PREPARATION', name: 'Распечатано' }, { id: 'CLIENT', name: 'На подписании' }, { id: 'UC_VEBS9Z', name: 'Передано курьеру' }, { id: 'UC_4SCTLL', name: 'Ожидает обратного отправления' }, { id: 'SUCCESS', name: 'Направлено-получено' } ]
    };

    const CFG = {
        LIST_ID: 28, SPA_ID: 1258,
        F_COMPANY: 'PROPERTY_674', F_ADDR: 'PROPERTY_108', F_TYPE: 'PROPERTY_768', F_DATE: 'PROPERTY_110',
        F_NUM: 'PROPERTY_676', F_FILE_EDIT: 'PROPERTY_124', F_FILE_PDF: 'PROPERTY_126', F_DEAL: 'PROPERTY_106', F_LINKED: 'PROPERTY_770',
        SPA_LINK_DOC: 'ufCrm70_1765294185', SPA_LINK_COMP: 'ufCrm70_1761052719', SPA_TRACK: 'ufCrm70_1761053173', SPA_DATE: 'ufCrm70_1765293859', SPA_FILE: 'ufCrm70_1761052939', SPA_CONFIRM: 'ufCrm70_1761052939'
    };

    const DIRS = [ { id: 82, name: 'Почта России', icon: 'bi-envelope' }, { id: 84, name: 'E-mail', icon: 'bi-at' }, { id: 86, name: 'ЭДО', icon: 'bi-file-earmark-text' }, { id: 88, name: 'Курьер', icon: 'bi-truck' } ];

    const app = {
        page: 0, items: [], compMap: {}, dealMap: {}, spaMap: {}, linkedMap: {}, stages: {}, listVals: {}, domain: '', searchTimer: null, 

        init: function() {
            window.app = app;
            BX24.init(() => {
                app.domain = 'https://' + BX24.getAuth().domain;
                app.prepareManualStages();
                app.loadFields();
                $(document).on('click', function(e) { if (!$(e.target).closest('.ac-wrapper').length) $('.ac-list').hide(); });
            });
            $('#search').on('keypress', (e) => { if(e.which == 13) app.reset(); });
        },

        prepareManualStages: function() {
            for(let dirId in MANUAL_STAGES) {
                let prefix = `DT${CFG.SPA_ID}_${dirId}`;
                app.stages[dirId] = MANUAL_STAGES[dirId].map(st => ({ STATUS_ID: st.id.includes(':') ? st.id : `${prefix}:${st.id}`, NAME: st.name }));
            }
        },

        loadFields: function() {
            $('#loader').show().find('div:last').text('Загрузка справочников...');
            BX24.callMethod('lists.field.get', { IBLOCK_TYPE_ID: 'bitrix_processes', IBLOCK_ID: CFG.LIST_ID }, res => {
                if(res.data()) {
                    let fields = res.data();
                    if(fields[CFG.F_COMPANY]) app.listVals[CFG.F_COMPANY] = fields[CFG.F_COMPANY].DISPLAY_VALUES_FORM;
                    if(fields[CFG.F_TYPE]) app.listVals[CFG.F_TYPE] = fields[CFG.F_TYPE].DISPLAY_VALUES_FORM;
                }
                app.renderFilters();
                app.loadList();
            });
        },

        renderFilters: function() {
            const fillSelect = (id, map) => {
                let $el = $(id); $el.find('option:not(:first)').remove();
                if(!map) return;
                for(let key in map) $el.append(`<option value="${key}">${map[key]}</option>`);
            };
            fillSelect('#filter-company', app.listVals[CFG.F_COMPANY]);
            fillSelect('#filter-type', app.listVals[CFG.F_TYPE]);
        },

        loadList: function() {
            $('#loader').show().find('div:last').text('Загрузка реестра...');
            $('#tbody').empty();
            app.items = [];
            let filter = {};
            let q = $('#search').val().trim();
            if(q) filter['%NAME'] = q;
            if($('#filter-company').val()) filter[CFG.F_COMPANY] = $('#filter-company').val();
            if($('#filter-type').val()) filter[CFG.F_TYPE] = $('#filter-type').val();

            BX24.callMethod('lists.element.get', {
                IBLOCK_TYPE_ID: 'bitrix_processes', IBLOCK_ID: CFG.LIST_ID, start: app.page * 50, FILTER: filter,
                SELECT: [ 'ID', 'NAME', CFG.F_COMPANY, CFG.F_ADDR, CFG.F_TYPE, CFG.F_DATE, CFG.F_NUM, CFG.F_FILE_EDIT, CFG.F_FILE_PDF, CFG.F_DEAL, CFG.F_LINKED ]
            }, res => {
                if(res.error()) { console.error(res.error()); return; }
                app.items = res.data();
                $('#btn-prev').prop('disabled', app.page === 0);
                $('#btn-next').prop('disabled', !res.more());
                $('#page-num').text('Страница ' + (app.page + 1));
                if(!app.items.length) { $('#loader').hide(); $('#tbody').html('<tr><td colspan="8" align="center" style="padding:30px; color:#999;">Данные не найдены</td></tr>'); return; }
                app.loadRelationsParallel();
            });
        },

        loadRelationsParallel: function() {
            let docIds = [], compIds = new Set(), dealIds = new Set(), linkedDocIds = new Set();
            app.items.forEach(it => {
                docIds.push(String(it.ID));
                let c = app.extractId(it[CFG.F_ADDR]); if(c > 0) compIds.add(c);
                let d = app.extractId(it[CFG.F_DEAL]); if(d > 0) dealIds.add(d);
                if(it[CFG.F_LINKED]) { let ls = (typeof it[CFG.F_LINKED] === 'object') ? Object.values(it[CFG.F_LINKED]) : [it[CFG.F_LINKED]]; ls.forEach(l => { if(l) linkedDocIds.add(l); }); }
            });

            Promise.all([
                new Promise(r => BX24.callMethod('crm.item.list', { entityTypeId: CFG.SPA_ID, filter: { ['=' + CFG.SPA_LINK_DOC]: docIds } }, res => {
                    app.spaMap = {}; (res.data()?.items || []).forEach(s => { let did = Array.isArray(s[CFG.SPA_LINK_DOC]) ? s[CFG.SPA_LINK_DOC][0] : s[CFG.SPA_LINK_DOC]; if(did) { if(!app.spaMap[did]) app.spaMap[did] = []; app.spaMap[did].push(s); } }); r();
                })),
                new Promise(r => { if(compIds.size===0) return r(); BX24.callMethod('crm.company.list', { filter: { 'ID': Array.from(compIds) }, select: ['ID', 'TITLE'] }, res => { (res.data()||[]).forEach(c => app.compMap[String(c.ID)] = c.TITLE); r(); }); }),
                new Promise(r => { if(dealIds.size===0) return r(); BX24.callMethod('crm.deal.list', { filter: { 'ID': Array.from(dealIds) }, select: ['ID', 'TITLE'] }, res => { (res.data()||[]).forEach(d => app.dealMap[String(d.ID)] = d.TITLE); r(); }); }),
                new Promise(r => { if(linkedDocIds.size===0) return r(); BX24.callMethod('lists.element.get', { IBLOCK_TYPE_ID: 'bitrix_processes', IBLOCK_ID: CFG.LIST_ID, FILTER: { 'ID': Array.from(linkedDocIds) }, SELECT: ['ID', 'NAME', CFG.F_NUM, CFG.F_DATE] }, res => { app.linkedMap={}; (res.data()||[]).forEach(i => app.linkedMap[String(i.ID)] = i); r(); }); })
            ]).then(() => app.render());
        },

        // === SEARCH ===
        onSearchInput: function(type, el) {
            let val = $(el).val().trim();
            if(val.length === 0) { $(`#res-${type}, #clear-${type}`).hide(); return; }
            $(`#clear-${type}`).show();
            clearTimeout(app.searchTimer);
            app.searchTimer = setTimeout(() => app.runSearch(type, val), 300);
        },
        runSearch: function(type, query) {
            if (type === 'linked') {
                // Поиск по списку (связанные документы)
                BX24.callMethod('lists.element.get', {
                    IBLOCK_TYPE_ID: 'bitrix_processes', IBLOCK_ID: CFG.LIST_ID,
                    FILTER: { '%NAME': query }, SELECT: ['ID', 'NAME', CFG.F_NUM]
                }, res => {
                    let items = res.data(), $list = $(`#res-${type}`); $list.empty();
                    if(!items || items.length === 0) $list.append('<div class="ac-item" style="cursor:default; color:#999">Ничего не найдено</div>');
                    else items.forEach(item => {
                        let num = item[CFG.F_NUM] ? Object.values(item[CFG.F_NUM])[0] : '';
                        let text = item.NAME + (num ? ` (№${num})` : '');
                        $list.append(`<div class="ac-item" onclick="app.selectResult('${type}', '${item.ID}', '${text.replace(/'/g, "&#39;")}')">${text}</div>`);
                    });
                    $list.show();
                });
            } else {
                // Поиск по CRM
                let method = (type === 'company') ? 'crm.company.list' : 'crm.deal.list';
                BX24.callMethod(method, { filter: { '%TITLE': query }, select: ['ID', 'TITLE'], order: { 'ID': 'DESC' } }, res => {
                    let items = res.data(), $list = $(`#res-${type}`); $list.empty();
                    if(!items || items.length === 0) $list.append('<div class="ac-item" style="cursor:default; color:#999">Ничего не найдено</div>');
                    else items.forEach(item => $list.append(`<div class="ac-item" onclick="app.selectResult('${type}', '${item.ID}', '${item.TITLE.replace(/'/g, "&#39;")}')">${item.TITLE}</div>`));
                    $list.show();
                });
            }
        },
        selectResult: function(type, id, title) {
            $(`#search-${type}`).val(title);
            if(type === 'company') $('#new-addr-id').val(id); 
            else if(type === 'deal') $('#new-deal-id').val(id);
            else if(type === 'linked') $('#new-linked-id').val(id);
            $(`#res-${type}`).hide();
        },
        clearSearch: function(type) {
            $(`#search-${type}`).val('');
            if(type === 'company') $('#new-addr-id').val(''); 
            else if(type === 'deal') $('#new-deal-id').val('');
            else if(type === 'linked') $('#new-linked-id').val('');
            $(`#res-${type}, #clear-${type}`).hide();
        },

        // === MODAL & FORM ===
        openModal: function(editId = null) {
            $('#edit-id').val('');
            $('#new-name, #new-num').val('');
            $('#new-date').val(new Date().toISOString().substr(0,10));
            $('#new-files').val('');
            app.clearSearch('company'); app.clearSearch('deal'); app.clearSearch('linked');
            $('#existing-files-block').hide(); $('#existing-files-list').empty();
            
            const fill = (id, map) => { let $el = $(id); $el.find('option:not(:first)').remove(); for(let k in map) $el.append(`<option value="${k}">${map[k]}</option>`); };
            fill('#new-company', app.listVals[CFG.F_COMPANY]); fill('#new-type', app.listVals[CFG.F_TYPE]);

            if(editId) {
                let item = app.items.find(i => i.ID == editId);
                if(item) {
                    $('#modal-title-text').text('Редактирование документа');
                    $('#edit-id').val(editId);
                    
                    $('#new-name').val(item.NAME);
                    $('#new-num').val(app.safeVal(item[CFG.F_NUM]));
                    $('#new-date').val(app.safeVal(item[CFG.F_DATE]));
                    if(app.safeVal(item[CFG.F_COMPANY])) $('#new-company').val(app.safeVal(item[CFG.F_COMPANY]));
                    if(app.safeVal(item[CFG.F_TYPE])) $('#new-type').val(app.safeVal(item[CFG.F_TYPE]));
                    
                    let compId = app.extractId(item[CFG.F_ADDR]);
                    if(compId) { $('#new-addr-id').val(compId); $('#search-company').val(app.compMap[compId] || `ID: ${compId}`); $('#clear-company').show(); }
                    
                    let dealId = app.extractId(item[CFG.F_DEAL]);
                    if(dealId) { $('#new-deal-id').val(dealId); $('#search-deal').val(app.dealMap[dealId] || `ID: ${dealId}`); $('#clear-deal').show(); }

                    // Заполняем поле "Связанный документ"
                    let linkRaw = item[CFG.F_LINKED];
                    if(linkRaw) {
                        let lids = (typeof linkRaw === 'object') ? Object.values(linkRaw) : [linkRaw];
                        let firstLid = lids[0];
                        if(firstLid && app.linkedMap[firstLid]) {
                            $('#new-linked-id').val(firstLid);
                            $('#search-linked').val(app.linkedMap[firstLid].NAME);
                            $('#clear-linked').show();
                        }
                    }

                    let exFiles = [];
                    const pushFiles = (raw) => app.getFiles(raw).forEach((u, idx) => exFiles.push(`<span class="existing-file-tag">Файл</span>`));
                    pushFiles(item[CFG.F_FILE_EDIT]); pushFiles(item[CFG.F_FILE_PDF]);
                    if(exFiles.length) { $('#existing-files-list').html(exFiles.join('')); $('#existing-files-block').show(); }
                }
            } else {
                $('#modal-title-text').text('Регистрация документа');
            }
            $('#create-modal').css('display', 'flex');
        },
        
        closeModal: function() { $('#create-modal').hide(); },

        saveForm: function() {
            let editId = $('#edit-id').val();
            let name = $('#new-name').val().trim();
            if(!name) { alert('Введите название!'); return; }
            $('#create-spinner').show();
            
            let files = document.getElementById('new-files').files, filePromises = [];
            const readFile = (file) => new Promise(resolve => { let r = new FileReader(); r.onload = e => resolve({ name: file.name, content: e.target.result.split(',')[1], type: file.type.includes('pdf') ? 'pdf' : 'edit' }); r.readAsDataURL(file); });
            for(let i=0; i<files.length; i++) filePromises.push(readFile(files[i]));

            Promise.all(filePromises).then(filesData => {
                let fields = {
                    'NAME': name,
                    [CFG.F_COMPANY]: $('#new-company').val(),
                    [CFG.F_TYPE]: $('#new-type').val(),
                    [CFG.F_NUM]: $('#new-num').val(),
                    [CFG.F_DATE]: $('#new-date').val(),
                };
                if(!editId) fields['IBLOCK_SECTION_ID'] = false;

                let addrId = $('#new-addr-id').val(); fields[CFG.F_ADDR] = addrId ? 'CO_' + addrId : (editId ? '' : null);
                let dealId = $('#new-deal-id').val(); fields[CFG.F_DEAL] = dealId ? 'D_' + dealId : (editId ? '' : null);
                
                // Связанный документ (множественное, но передаем одно)
                let linkedId = $('#new-linked-id').val();
                if(linkedId) fields[CFG.F_LINKED] = { 'n0': linkedId };
                else if(editId) fields[CFG.F_LINKED] = ''; 

                let pdfFiles = [], editFiles = [];
                filesData.forEach(f => { let fObj = { 'NAME': f.name, 'content': f.content }; if(f.type === 'pdf') pdfFiles.push(fObj); else editFiles.push(fObj); });

                const prepareFiles = (newArr, oldRaw) => {
                    let res = {}, idx = 0;
                    if(editId && oldRaw) ((typeof oldRaw==='object'&&oldRaw!==null)?Object.values(oldRaw):[oldRaw]).forEach(id => { let val = (typeof id==='object'&&id.id)?id.id:id; if(parseInt(val)>0) res['n'+(idx++)] = {'VALUE':val}; });
                    newArr.forEach(f => res['n'+(idx++)] = {'VALUE':f});
                    return Object.keys(res).length > 0 ? res : null;
                };

                let currentItem = editId ? app.items.find(i=>i.ID == editId) : null;
                let finalPdf = prepareFiles(pdfFiles, currentItem ? currentItem[CFG.F_FILE_PDF] : null);
                if(finalPdf) fields[CFG.F_FILE_PDF] = finalPdf;
                let finalEdit = prepareFiles(editFiles, currentItem ? currentItem[CFG.F_FILE_EDIT] : null);
                if(finalEdit) fields[CFG.F_FILE_EDIT] = finalEdit;

                let method = editId ? 'lists.element.update' : 'lists.element.add';
                let params = { IBLOCK_TYPE_ID: 'bitrix_processes', IBLOCK_ID: CFG.LIST_ID, FIELDS: fields };
                if(editId) params.ELEMENT_ID = editId; else params.ELEMENT_CODE = Date.now();

                BX24.callMethod(method, params, res => {
                    $('#create-spinner').hide();
                    if(res.error()) alert('Ошибка: ' + res.error().ex.error_description); else { app.closeModal(); app.reset(); }
                });
            });
        },

        // === RENDER ===
        render: function() {
            let html = '';
            app.items.forEach(it => {
                let docId = String(it.ID);
                let num = app.safeVal(it[CFG.F_NUM]);
                let date = app.safeVal(it[CFG.F_DATE]);
                let myCompanyText = app.getTextVal(CFG.F_COMPANY, it[CFG.F_COMPANY]);
                let docTypeText   = app.getTextVal(CFG.F_TYPE, it[CFG.F_TYPE]);
                let compId = app.extractId(it[CFG.F_ADDR]);
                let compName = compId ? (app.compMap[String(compId)] || `ID:${compId}`) : '-';
                let dealId = app.extractId(it[CFG.F_DEAL]);
                let filesHtml = '';
                const ri = (l, c) => l.forEach(u => filesHtml += `<a href="${u}" target="_blank" class="bi bi-file-earmark-arrow-down-fill file-icon ${c}"></a>`);
                ri(app.getFiles(it[CFG.F_FILE_EDIT]), 'icon-doc'); ri(app.getFiles(it[CFG.F_FILE_PDF]), 'icon-pdf');

                let linkedHtml = '';
                if(it[CFG.F_LINKED]) { let ids = (typeof it[CFG.F_LINKED] === 'object') ? Object.values(it[CFG.F_LINKED]) : [it[CFG.F_LINKED]]; ids.forEach(lid => { let l = app.linkedMap[String(lid)]; linkedHtml += `<div class="doc-mini-card"><span class="link-entity" onclick="BX24.openPath('/company/lists/${CFG.LIST_ID}/element/0/?list_id=${CFG.LIST_ID}&element_id=${lid}')">${l?l.NAME:'Doc #'+lid}</span><br><small style="color:#64748b;">№ ${l?app.safeVal(l[CFG.F_NUM]):''} от ${l?app.safeVal(l[CFG.F_DATE]):''}</small></div>`; }); }

                html += `<tr>
                    <td><i class="bi bi-pencil-square edit-btn" onclick="app.openModal('${docId}')" title="Редактировать"></i></td>
                    <td><div style="font-weight:600; margin-bottom:2px;">${myCompanyText}</div><small style="color:#64748b;"><span class="link-entity" onclick="BX24.openPath('/crm/company/details/${compId}/')">${compName}</span></small></td>
                    <td>${docTypeText}</td>
                    <td><div style="font-weight:600; color:#3b82f6; margin-bottom:2px;">${it.NAME}</div><small style="color:#64748b;">№ ${num} от ${date}</small></td>
                    <td>${filesHtml}</td>
                    <td>${linkedHtml}</td>
                    <td>${dealId ? `<span class="link-entity" onclick="BX24.openPath('/crm/deal/details/${dealId}/')">${app.dealMap[String(dealId)]||'ID:'+dealId}</span>` : ''}</td>
                    <td>${app.renderLogistics(docId, compId)}</td>
                </tr>`;
            });
            $('#tbody').html(html); $('#loader').hide(); BX24.fitWindow();
        },

        renderLogistics: function(docId, compId) {
            let rows = '';
            DIRS.forEach(dir => {
                let spa = (app.spaMap[docId] || []).find(s => s.categoryId == dir.id);
                let checked = !!spa; let spaId = spa ? spa.id : 0;
                let opt = checked ? ((app.stages[dir.id]||[]).map(s => `<option value="${s.STATUS_ID}" ${(spa.stageId==s.STATUS_ID)?'selected':''}>${s.NAME}</option>`).join('')) : '';
                if(checked && !opt && spa.stageId) opt = `<option value="${spa.stageId}" selected>${spa.stageId}</option>`;
                let conf = ''; if(checked) app.getFiles(spa[CFG.SPA_CONFIRM]).forEach(u => conf += `<a href="${u}" target="_blank" class="conf-file"><i class="bi bi-file-earmark-arrow-down"></i>Файл</a>`);

                rows += `<div class="log-item ${checked?'active':''}" id="log-${spaId}" ondragover="app.handleDragOver(event, this)" ondragleave="app.handleDragLeave(event, this)" ondrop="app.handleDrop(event, ${spaId})">
                    <div class="log-row-main">
                        <div class="log-left"><input type="checkbox" onchange="app.toggleSpa(this, '${docId}', ${dir.id}, ${compId||0})" ${checked?'checked':''} data-spaid="${spaId}"> <i class="bi ${dir.icon}"></i> ${dir.name}</div>
                        ${checked ? `<div class="log-right"><select class="inp" onchange="app.save(${spaId}, 'stageId', this.value)" style="width:110px">${opt}</select><input class="inp" value="${spa[CFG.SPA_TRACK]||''}" onblur="app.save(${spaId}, '${CFG.SPA_TRACK}', this.value)" style="width:80px" placeholder="Трек"><input type="date" class="inp" value="${(spa[CFG.SPA_DATE]||'').substr(0,10)}" onblur="app.save(${spaId}, '${CFG.SPA_DATE}', this.value)" style="width:95px"></div>` : ''}
                    </div>
                    ${checked ? `<div class="log-row-files"><label class="upload-btn"><i class="bi bi-paperclip"></i><input type="file" onchange="app.handleFile(this, ${spaId})" hidden></label><div style="font-size:11px; color:#94a3b8; margin-right:5px;">Файлы...</div>${conf}<div id="spin-${spaId}" class="spinner-border spinner-border-sm text-primary" style="display:none;"></div></div>` : ''}
                </div>`;
            });
            return `<div class="log-wrapper">${rows}</div>`;
        },

        // === UTILS ===
        handleDragOver: function(e, el) { e.preventDefault(); e.stopPropagation(); $(el).addClass('dragover'); },
        handleDragLeave: function(e, el) { e.preventDefault(); e.stopPropagation(); $(el).removeClass('dragover'); },
        handleDrop: function(e, spaId) { e.preventDefault(); e.stopPropagation(); $(`#log-${spaId}`).removeClass('dragover'); if(e.dataTransfer.files.length) app.upload(spaId, e.dataTransfer.files[0]); },
        handleFile: function(inp, spaId) { if(inp.files.length) app.upload(spaId, inp.files[0]); },
        
        upload: function(spaId, file) {
            $(`#spin-${spaId}`).show();
            let r = new FileReader();
            r.onload = e => {
                let b64 = e.target.result.split(',')[1];
                BX24.callMethod('crm.item.get', { entityTypeId: CFG.SPA_ID, id: spaId }, res => {
                    let old = [], raw = res.data()?.item?.[CFG.SPA_CONFIRM];
                    if(raw) (Array.isArray(raw)?raw:Object.values(raw)).forEach(f => { let id = (typeof f === 'object' && f.id) ? f.id : f; if(parseInt(id)>0) old.push(id); });
                    old.push([file.name, b64]);
                    BX24.callMethod('crm.item.update', { entityTypeId: CFG.SPA_ID, id: spaId, fields: { [CFG.SPA_CONFIRM]: old } }, r2 => { $(`#spin-${spaId}`).hide(); if(r2.error()) alert(r2.error().ex.error_description); else app.loadRelationsParallel(); });
                });
            };
            r.readAsDataURL(file);
        },
        
        getFiles: function(r) { if(!r) return []; return ((typeof r==='object'&&r!==null)?Object.values(r):[r]).map(i => { let u = (typeof i==='object'?(i.src||i.SRC||i.url):'') || (parseInt(i)>0?`/bitrix/tools/disk/uf.php?attachedId=${i}&action=download&ncc=1`:''); return (u && !u.startsWith('http') ? app.domain+u : u); }).filter(u=>u); },
        getTextVal: function(c, r) { if(!r) return ''; let ids = (typeof r==='object')?Object.values(r):[r]; let t = ids.map(i => app.listVals[c]?.[i]||i); return c===CFG.F_TYPE ? t.map(v=>`<div class="type-badge">${v}</div>`).join(' ') : t.join(', '); },
        toggleSpa: function(cb, did, cid, co) { if(cb.checked) { let f={categoryId:cid,[CFG.SPA_LINK_DOC]:did}; if(co>0) f[CFG.SPA_LINK_COMP]=co; f.stageId=(app.stages[cid]&&app.stages[cid][0])?app.stages[cid][0].STATUS_ID:`DT${CFG.SPA_ID}_${cid}:NEW`; BX24.callMethod('crm.item.add',{entityTypeId:CFG.SPA_ID,fields:f},r=>{if(r.error()){alert(r.error().ex.error_description);cb.checked=false;}else app.loadRelationsParallel();}); } else { let sid=$(cb).data('spaid'); if(!sid)return; if(confirm('Удалить?')) BX24.callMethod('crm.item.delete',{entityTypeId:CFG.SPA_ID,id:sid},()=>app.loadRelationsParallel()); else cb.checked=true; } },
        save: function(id, f, v) { BX24.callMethod('crm.item.update', { entityTypeId: CFG.SPA_ID, id: id, fields: { [f]: v } }); },
        extractId: function(r) { return r ? parseInt(((typeof r==='object'?Object.values(r)[0]:r)+'').replace(/\D/g,''))||0 : 0; },
        safeVal: function(r) { return r ? ((typeof r==='object'?Object.values(r)[0]:r)||'') : ''; },
        reset: function() { app.page = 0; app.loadList(); }, next: function() { app.page++; app.loadList(); }, prev: function() { if(app.page>0) app.page--; app.loadList(); }
    };
    app.init();
</script>
</body>
</html>