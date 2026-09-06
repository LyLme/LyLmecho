/*!
 * LyLmecho 默认主题 lylmeblog —— 导航页交互
 * 职责：
 *   1. 搜索引擎：下拉切换 + 记忆 + 占位提示（external=新窗口 / go=站内跳转）
 *   2. 联想词：输入时优先给「站内直达」（匹配收录链接），百度/必应引擎再追加网页联想词
 *   3. 站内直达：任意引擎输入时都在联想词列表优先匹配收录链接
 *   4. 顶栏时钟（可选模块）
 * 说明：
 *   - 统一用 <a rel="noopener">.click() 开新窗口。切勿用 window.open + opener 判空做回退，
 *     部分浏览器(如 Firefox)对带 noopener 的 window.open 一律返回 null，会误触发本页跳转，
 *     造成“当前页与新窗口同时跳搜索”。
 *   - 全部 ES5，避免低版本/兼容模式出问题。
 */
(function (doc, win, storage) {
    'use strict';

    var ready = function (fn) {
        if (doc.readyState === 'loading') {
            doc.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    var enc = function (s) { return encodeURIComponent(s); };

    /* 只开新标签页（不操作当前页） */
    function openBlank(url) {
        var a = doc.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        doc.body.appendChild(a);
        a.click();
        doc.body.removeChild(a);
    }

    ready(function () {

        /* =====================================================
         * 0. 站内链接索引入口（联想词「站内直达」匹配用）
         * ===================================================== */
        var groupsWrap = doc.querySelector('.lnav-groups');
        var bar = doc.getElementById('lnav-filterbar');
        var barText = doc.getElementById('lnav-filtercount');
        var clearBtn = doc.getElementById('lnav-filterclear');

        var textOf = function (tile) {
            return (tile.textContent || '').replace(/\s+/g, ' ').toLowerCase();
        };

        var doFilter = function (kw) {
            var tokens = (kw || '').toLowerCase().split(/\s+/).filter(Boolean);
            var tileList = groupsWrap ? groupsWrap.querySelectorAll('.lnav-tile') : [];
            var groups = groupsWrap ? groupsWrap.querySelectorAll('.lnav-group') : [];
            var total = 0;
            var shown = 0;
            var i;

            for (i = 0; i < tileList.length; i++) {
                var text = textOf(tileList[i]);
                var hit = tokens.length === 0;
                for (var j = 0; j < tokens.length; j++) {
                    if (text.indexOf(tokens[j]) === -1) { hit = false; break; }
                    hit = true;
                }
                tileList[i].classList.toggle('hidden', !hit);
                total++;
                if (hit) { shown++; }
            }

            for (i = 0; i < groups.length; i++) {
                var tiles = groups[i].querySelectorAll('.lnav-tile');
                var visibleTiles = 0;
                for (var k = 0; k < tiles.length; k++) {
                    if (!tiles[k].classList.contains('hidden')) { visibleTiles++; }
                }
                groups[i].classList.toggle('nomatch', visibleTiles === 0);
                groups[i].classList.toggle('hidden', visibleTiles === 0);
            }

            if (tokens.length && bar) {
                bar.classList.add('show');
                if (barText) { barText.textContent = shown + ' / ' + total; }
            } else if (bar) {
                bar.classList.remove('show');
            }
        };

        var clearFilter = function () {
            var tileList = groupsWrap ? groupsWrap.querySelectorAll('.lnav-tile') : [];
            var groups = groupsWrap ? groupsWrap.querySelectorAll('.lnav-group') : [];
            for (var i = 0; i < tileList.length; i++) { tileList[i].classList.remove('hidden'); }
            for (var n = 0; n < groups.length; n++) { groups[n].classList.remove('nomatch', 'hidden'); }
            if (bar) { bar.classList.remove('show'); }
            if (input) { input.value = ''; }
            if (input) { input.focus(); }
        };

        /* =====================================================
         * 1. 搜索引擎下拉：切换 + 记忆 + 占位提示
         * ===================================================== */
        var form = doc.getElementById('search-form');
        var input = doc.getElementById('search-input');
        var engineWrap = doc.getElementById('lhp-engine-wrap');
        var enginePop = doc.getElementById('lhp-engine-pop');
        var engineBtn = doc.getElementById('lhp-engine-btn');
        var engineIco = doc.getElementById('lhp-engine-ico');
        var engineName = doc.getElementById('lhp-engine-name');
        var KEY = 'lylme_sou_engine';
        var engineOpts = [];
        var current = null;

        /* 浮层挂载到 body + fixed 定位，彻底脱离祖先的 overflow / 层叠上下文裁切
         * （base.css 的 .lylme-hero{overflow:hidden} 及其形成的层叠上下文会盖住下拉 / 联想层） */
        var placeLayer = function (layer, rect, opts) {
            opts = opts || {};
            if (layer.parentNode !== doc.body) { doc.body.appendChild(layer); }
            layer.style.position = 'fixed';
            layer.style.zIndex = '2000';
            layer.style.left = rect.left + 'px';
            layer.style.width = (opts.width || rect.width) + 'px';
            layer.style.top = (rect.bottom + (opts.gap || 6)) + 'px';
        };
        var placeEngine = function () {
            if (!enginePop || !engineBtn) { return; }
            var r = engineBtn.getBoundingClientRect();
            var w = Math.min(248, Math.max(r.width, win.innerWidth * 0.78));
            var left = Math.min(r.left, win.innerWidth - w - 8);
            if (left < 8) { left = 8; }
            placeLayer(enginePop, r, { width: w, gap: 8 });
            enginePop.style.left = left + 'px';
        };
        var placeSuggest = function () {
            if (!suggestEl || !inputWrap) { return; }
            placeLayer(suggestEl, inputWrap.getBoundingClientRect(), { gap: 6 });
        };
        var repositionFloats = function () {
            if (enginePop && !enginePop.hidden) { placeEngine(); }
            if (suggestEl && !suggestEl.hidden) { placeSuggest(); }
        };
        win.addEventListener('scroll', repositionFloats, true);
        win.addEventListener('resize', repositionFloats);

        if (enginePop) {
            engineOpts = [].slice.call(enginePop.querySelectorAll('.lhp-eng-opt'));
        }

        var engineOf = function (opt) {
            if (!opt) { return null; }
            var ico = opt.querySelector('.lhp-eng-ico');
            return {
                alias: opt.getAttribute('data-alias') || '',
                mode: opt.getAttribute('data-mode') || 'external',
                link: opt.getAttribute('data-link') || '',
                hint: opt.getAttribute('data-hint') || '',
                name: opt.getAttribute('data-name') || '',
                icon: ico ? ico.innerHTML : '<i class="mdi mdi-web"></i>'
            };
        };

        var engineByAlias = function (alias) {
            for (var i = 0; i < engineOpts.length; i++) {
                if (engineOpts[i].getAttribute('data-alias') === alias) { return engineOpts[i]; }
            }
            return null;
        };

        var closeEnginePop = function () {
            if (!enginePop || enginePop.hidden) { return; }
            enginePop.hidden = true;
            if (engineBtn) { engineBtn.setAttribute('aria-expanded', 'false'); }
        };

        var applyEngine = function (opt) {
            if (!opt) { return; }
            var eng = engineOf(opt);
            current = eng;
            if (engineIco) { engineIco.innerHTML = eng.icon; }
            if (engineName) { engineName.textContent = eng.name; }
            if (input && eng.hint) { input.setAttribute('placeholder', eng.hint); }
            for (var i = 0; i < engineOpts.length; i++) {
                var sel = engineOpts[i] === opt;
                engineOpts[i].setAttribute('aria-selected', sel ? 'true' : 'false');
                engineOpts[i].classList.toggle('is-active', sel);
            }
            try { storage.setItem(KEY, eng.alias); } catch (e) {}
        };

        /* 默认引擎：记忆值 > 服务端 data-default > 第一项 */
        var initEngine = function () {
            var saved = null;
            try { saved = storage.getItem(KEY); } catch (e) {}
            var opt = saved ? engineByAlias(saved) : null;
            if (!opt && enginePop) {
                opt = engineByAlias(enginePop.getAttribute('data-default') || '');
            }
            if (!opt && engineOpts.length) { opt = engineOpts[0]; }
            applyEngine(opt);
        };

        if (engineBtn) {
            engineBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!enginePop) { return; }
                var willOpen = enginePop.hidden;
                closeEnginePop();
                if (willOpen) {
                    placeEngine();
                    enginePop.hidden = false;
                    engineBtn.setAttribute('aria-expanded', 'true');
                }
                hideSuggest();
            });
        }

        if (enginePop) {
            enginePop.addEventListener('click', function (e) {
                var opt = e.target && e.target.closest ? e.target.closest('.lhp-eng-opt') : null;
                if (!opt) { return; }
                e.preventDefault();
                applyEngine(opt);
                closeEnginePop();
                hideSuggest();
                if (input) { input.focus(); }
            });
        }

        /* =====================================================
         * 2. 联想词 / 站内直达
         * ===================================================== */
        var suggestEl = doc.getElementById('lhp-suggest');
        var SUG = { items: [], idx: -1, seq: 0, timer: null };
        var jsonpSeq = 0;

        var showSuggest = function () {
            if (!suggestEl) { return; }
            placeSuggest();
            suggestEl.hidden = false;
        };
        var clearSuggest = function () {
            if (!suggestEl) { return; }
            suggestEl.innerHTML = '';
            SUG.items = [];
            SUG.idx = -1;
        };
        var hideSuggest = function () {
            if (!suggestEl) { return; }
            suggestEl.hidden = true;
            clearSuggest();
        };

        /* 收集本站链接缓存 */
        var siteRefs = null;
        var buildSiteRefs = function () {
            siteRefs = [];
            if (!groupsWrap) { return; }
            var tiles = groupsWrap.querySelectorAll('.lnav-tile');
            for (var i = 0; i < tiles.length; i++) {
                var tile = tiles[i];
                var nameEl = tile.querySelector('.lnav-tile-name');
                var descEl = tile.querySelector('.lnav-tile-desc');
                var icoEl = tile.querySelector('.lnav-tile-ico');
                var name = nameEl ? (nameEl.textContent || '').trim() : '';
                var desc = descEl ? (descEl.textContent || '').trim() : '';
                var href = tile.getAttribute('href') || '';
                if (name && href) {
                    siteRefs.push({ name: name, desc: desc, href: href, ico: icoEl ? icoEl.innerHTML : '' });
                }
            }
        };

        var matchSites = function (kw) {
            if (!siteRefs) { buildSiteRefs(); }
            if (!siteRefs.length || !kw) { return []; }
            var words = kw.toLowerCase();
            var out = [];
            var seen = {};
            for (var i = 0; i < siteRefs.length && out.length < 8; i++) {
                var r = siteRefs[i];
                var blob = (r.name + ' ' + r.desc).toLowerCase();
                if (blob.indexOf(words) !== -1 && !seen[r.name]) {
                    seen[r.name] = true;
                    out.push({ type: 'site', text: r.name, sub: r.desc, href: r.href, ico: r.ico });
                }
            }
            return out;
        };

        /* 网页联想词统一走百度联想 API（无论当前选中哪个外部引擎；失败静默忽略） */
        var remoteSugUrl = function (alias, kw) {
            return { url: 'https://suggestion.baidu.com/su?wd=' + enc(kw) + '&cb={cb}', charset: 'gbk' };
        };

        var loadJsonp = function (url, charset, cb) {
            var cbName = '_lylme_sug_' + (++jsonpSeq);
            var timer = null;
            var scr = null;
            var done = false;

            var cleanup = function () {
                if (done) { return; }
                done = true;
                if (timer) { win.clearTimeout(timer); }
                if (scr && scr.parentNode) { scr.parentNode.removeChild(scr); }
                try { delete win[cbName]; } catch (e) { win[cbName] = null; }
            };

            win[cbName] = function (data) {
                cleanup();
                cb(data || null);
            };

            /* error / timeout 可能触发多次, 回调可能已被清理, 统一幂等处理 */
            var fireNull = function () {
                try {
                    if (typeof win[cbName] === 'function') {
                        win[cbName](null);
                    } else {
                        cleanup();
                    }
                } catch (e) { cleanup(); }
            };

            scr = doc.createElement('script');
            scr.async = true;
            scr.src = url.indexOf('{cb}') !== -1 ? url.replace('{cb}', cbName) : url + (url.indexOf('?') === -1 ? '?' : '&') + 'cb=' + cbName;
            if (charset) {
                try { scr.setAttribute('charset', charset); } catch (e) {}
            }
            scr.onerror = fireNull;
            doc.body.appendChild(scr);
            timer = win.setTimeout(fireNull, 3500);
        };

        var collectWords = function (data) {
            var words = [];
            var i;
            if (!data) { return words; }
            if (typeof data.s !== 'undefined') { words = data.s; }
            else if (data.SearchResult) {
                for (i = 0; i < data.SearchResult.length; i++) {
                    var sg = data.SearchResult[i] && data.SearchResult[i].Suggests;
                    if (!sg) { continue; }
                    for (var j = 0; j < sg.length; j++) {
                        if (sg[j] && typeof sg[j].Txt !== 'undefined') { words.push(sg[j].Txt); }
                    }
                }
            } else if (Object.prototype.toString.call(data) === '[object Array]') {
                words = data[1] || [];
            }
            var out = [];
            for (i = 0; i < words.length; i++) {
                var w = String(words[i] || '').trim();
                if (w && out.indexOf(w) === -1) { out.push(w); }
            }
            return out.slice(0, 6);
        };

        var appendSug = function (item) {
            if (!suggestEl || !item || !item.text) { return; }
            var isSite = item.type === 'site';
            var li = doc.createElement('li');
            li.className = 'lhp-sug-item ' + (isSite ? 'is-site' : 'is-web');
            li.setAttribute('role', 'option');

            var ico = doc.createElement('span');
            ico.className = 'sug-ico';
            if (isSite) {
                ico.innerHTML = item.ico || '<i class="mdi mdi-link-variant"></i>';
            } else {
                ico.innerHTML = '<i class="mdi mdi-magnify"></i>';
            }
            li.appendChild(ico);

            var txt = doc.createElement('span');
            txt.className = 'sug-text';
            var nameEl = doc.createElement('span');
            nameEl.className = 'sug-name';
            nameEl.textContent = item.text;
            txt.appendChild(nameEl);
            if (item.sub) {
                var subEl = doc.createElement('span');
                subEl.className = 'sug-sub';
                subEl.textContent = item.sub;
                txt.appendChild(subEl);
            }
            li.appendChild(txt);

            if (isSite) {
                var g = doc.createElement('em');
                g.className = 'sug-tag';
                g.textContent = '直达';
                li.appendChild(g);
            }

            li.addEventListener('click', function () { chooseSug(item); });
            suggestEl.appendChild(li);
            SUG.items.push(item);
            showSuggest();
        };

        /* 联想项选中：本站直达开新页；网页词用当前引擎搜索 */
        var chooseSug = function (item) {
            if (!item) { return; }
            hideSuggest();
            if (item.type === 'site') {
                if (item.href) { openBlank(item.href); }
                return;
            }
            if (input) { input.value = item.text; }
            submitSearch();
        };

        var runSuggest = function (kw) {
            if (!suggestEl) { return; }
            clearSuggest();
            var seq = ++SUG.seq;
            var eng = current;
            var mode = eng ? eng.mode : 'external';

            /* 站内直达：任何引擎都先把匹配的收录链接放进联想词列表（优先展示） */
            var hits = matchSites(kw);
            for (var n = 0; n < hits.length; n++) { appendSug(hits[n]); }

            /* 外部搜索引擎再追加网页联想词；博客等模式不再追加 */
            if (mode !== 'external') {
                if (!SUG.items.length) { hideSuggest(); }
                return;
            }
            var prov = remoteSugUrl('baidu', kw);
            if (!prov) { if (!SUG.items.length) { hideSuggest(); } return; }
            loadJsonp(prov.url, prov.charset, function (data) {
                if (seq !== SUG.seq) { return; } // 过期结果丢弃
                var words = collectWords(data);
                for (var m = 0; m < words.length; m++) {
                    appendSug({ type: 'web', text: words[m] });
                }
                if (!SUG.items.length) { hideSuggest(); }
            });
        };

        if (input && suggestEl) {
            input.addEventListener('input', function () {
                closeEnginePop();
                var val = this.value.trim();
                if (SUG.timer) { win.clearTimeout(SUG.timer); }
                if (!val) { hideSuggest(); return; }
                SUG.timer = win.setTimeout(function () { runSuggest(val); }, 140);
            });

            input.addEventListener('focus', function () {
                var val = this.value.trim();
                if (val && SUG.items.length) { showSuggest(); }
            });

            input.addEventListener('keydown', function (e) {
                var open = suggestEl && !suggestEl.hidden && SUG.items.length > 0;
                if (e.key === 'ArrowDown' && open) {
                    e.preventDefault();
                    SUG.idx = (SUG.idx + 1) % SUG.items.length;
                    highlightSug();
                } else if (e.key === 'ArrowUp' && open) {
                    e.preventDefault();
                    SUG.idx = (SUG.idx - 1 + SUG.items.length) % SUG.items.length;
                    highlightSug();
                } else if (e.key === 'Enter' && open && SUG.idx >= 0) {
                    e.preventDefault();
                    chooseSug(SUG.items[SUG.idx]);
                } else if (e.key === 'Escape') {
                    hideSuggest();
                    closeEnginePop();
                }
            });
        }

        var highlightSug = function () {
            if (!suggestEl) { return; }
            var lis = suggestEl.querySelectorAll('.lhp-sug-item');
            for (var i = 0; i < lis.length; i++) {
                lis[i].classList.toggle('is-active', i === SUG.idx);
            }
        };

        /* 点击外部收起两层浮层（浮层已挂到 body，需同时判断浮层自身） */
        doc.addEventListener('click', function (e) {
            var t = e.target;
            if (engineWrap && !engineWrap.contains(t) && !(enginePop && enginePop.contains(t))) { closeEnginePop(); }
            if (inputWrap && !inputWrap.contains(t) && !(suggestEl && suggestEl.contains(t))) {
                hideSuggest();
            }
        });

        /* =====================================================
         * 3. 提交搜索（统一入口, 单次跳转）
         * ===================================================== */
        var inputWrap = doc.getElementById('lhp-input-wrap');

        var submitSearch = function () {
            if (!current) { return; }
            var kw = (input && input.value || '').trim();
            if (!kw) { if (input) { input.focus(); } return; }

            if (current.mode === 'filter') {
                hideSuggest();
                closeEnginePop();
                doFilter(kw);
                return;
            }
            if (current.mode === 'go') {
                /* 博客文章搜索：GET s 参数即可（article 模块兼容） */
                var sep = current.link.indexOf('?') !== -1 ? '&' : '?';
                win.location.href = current.link + sep + 's=' + enc(kw);
                return;
            }
            if (!current.link) { return; }
            openBlank(current.link + enc(kw));
            if (input) { input.value = ''; }
        };

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitSearch();
            });
        }


        /* 顶栏搜索：data-topbar="filter" = 站内即时过滤；blog 表单原生提交到博客 */
        var topbarFilter = doc.querySelector('form[data-topbar="filter"]');
        if (topbarFilter) {
            var topbarInput = topbarFilter.querySelector('input[type="text"]');
            topbarFilter.addEventListener('submit', function (e) {
                e.preventDefault();
                if (topbarInput) { doFilter(topbarInput.value); }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearFilter();
            });
        }

        /* 初始化 */
        if (engineOpts.length) { initEngine(); }

        /* =====================================================
         * 4. 顶栏时钟
         * ===================================================== */
        var clock = doc.getElementById('clock-time');
        var clockDate = doc.getElementById('clock-date');
        if (clock || clockDate) {
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            var tick = function () {
                var d = new Date();
                if (clock) { clock.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()); }
                if (clockDate) {
                    var week = ['日', '一', '二', '三', '四', '五', '六'][d.getDay()];
                    clockDate.textContent = (d.getMonth() + 1) + '月' + d.getDate() + '日 星期' + week;
                }
            };
            tick();
            win.setInterval(tick, 1000);
        }
    });
})(document, window, window.localStorage);
