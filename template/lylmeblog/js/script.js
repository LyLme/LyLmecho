/*!
 * LyLme Modern 主题 - 交互脚本
 * 依赖: 已加载 jQuery / Bootstrap bundle / PerfectScrollbar / main.min.js (后台基座)
 * 本脚本用原生 JS, 与基座互不干扰; 兼容层未定义的 TypechoComment 在此实现。
 * 版本 1.0 / 2026-09-05
 */
(function () {
    'use strict';

    var doc = document;
    var rootEl = doc.documentElement;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
    function store(k, v) { try { v === undefined ? localStorage.removeItem(k) : localStorage.setItem(k, v); } catch (e) {} }
    function read(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

    /* =========================================================
       1. 明暗配色切换 (与后台共用 localStorage.theme: 'dark' | 'default')
       ========================================================= */
    function currentTheme() {
        return rootEl.getAttribute('data-theme') || 'default';
    }
    function applyTheme(t) {
        rootEl.setAttribute('data-theme', t);
        if (doc.body) doc.body.setAttribute('data-theme', t);
        var btn = $('#lylme-scheme-toggle i');
        if (btn) btn.className = 'mdi ' + (t === 'dark' ? 'mdi-white-balance-sunny' : 'mdi-weather-night');
    }
    // 切换瞬间挂上 .lylme-theming, 让配色平滑过渡 (平时不带, 避免全局 transition 开销)
    function applyThemeSmooth(t) {
        if (reduceMotion) { applyTheme(t); return; }
        rootEl.classList.add('lylme-theming');
        applyTheme(t);
        setTimeout(function () { rootEl.classList.remove('lylme-theming'); }, 320);
    }
    function initScheme() {
        applyTheme(currentTheme());
        var toggle = $('#lylme-scheme-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var next = currentTheme() === 'dark' ? 'default' : 'dark';
                applyThemeSmooth(next);
                store('theme', next);
                window.dispatchEvent(new CustomEvent('lylme:theme', { detail: next }));
            });
        }
    }

    /* =========================================================
       2. 轻提示 Toast
       ========================================================= */
    var toastTimer = null;
    function toast(msg) {
        var el = $('#lylme-toast');
        if (!el) {
            el = doc.createElement('div');
            el.id = 'lylme-toast';
            el.className = 'lylme-toast';
            el.setAttribute('role', 'status');
            doc.body.appendChild(el);
        }
        el.innerHTML = '<i class="mdi mdi-check-circle-outline"></i>';
        el.appendChild(doc.createTextNode(msg || '已完成'));
        // 触发过渡
        void el.offsetWidth;
        el.classList.add('show');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.classList.remove('show'); }, 2200);
    }

    /* =========================================================
       3. 复制到剪贴板 (含降级)
       ========================================================= */
    function copyText(text, cb) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { cb && cb(true); }, function () { fallback(); });
        } else {
            fallback();
        }
        function fallback() {
            var ta = doc.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            doc.body.appendChild(ta);
            ta.select();
            var ok = false;
            try { ok = doc.execCommand('copy'); } catch (e) { ok = false; }
            doc.body.removeChild(ta);
            cb && cb(ok);
        }
    }

    /* =========================================================
       4. 评论回复 (兼容层未定义 TypechoComment, 于此实现)
       ========================================================= */
    var MARKER = 'lylme-respond-marker';
    var TypechoComment = {
        reply: function (coid, cid, link) {
            var respond = $('.lylme-respond');
            var target = doc.getElementById(coid);
            if (!respond || !target) return false;
            if (!doc.getElementById(MARKER)) {
                var marker = doc.createElement('div');
                marker.id = MARKER;
                respond.parentNode.insertBefore(marker, respond);
            }
            target.appendChild(respond);
            var parent = doc.getElementById('comment-parent');
            if (parent) parent.value = cid;
            var cancel = doc.getElementById('cancel-comment-reply-link');
            if (cancel) cancel.style.display = '';
            var ta = doc.getElementById('textarea');
            respond.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
            if (ta) setTimeout(function () { ta.focus(); }, reduceMotion ? 0 : 300);
            return false;
        },
        cancelReply: function () {
            var respond = $('.lylme-respond');
            var marker = doc.getElementById(MARKER);
            if (respond && marker && marker.parentNode) {
                marker.parentNode.insertBefore(respond, marker.nextSibling);
                marker.parentNode.removeChild(marker);
            }
            var parent = doc.getElementById('comment-parent');
            if (parent) parent.value = 0;
            var cancel = doc.getElementById('cancel-comment-reply-link');
            if (cancel) cancel.style.display = 'none';
            return false;
        }
    };
    window.TypechoComment = TypechoComment;

    /* =========================================================
       5. 文章目录 TOC (h2/h3/h4) + 折叠 + 滚动高亮
       ========================================================= */
    function initToc() {
        var toc = $('#lylme-toc');
        var body = $('#lylme-post-content');
        if (!toc || !body) return;
        var heads = $$('h2, h3, h4', body);
        if (heads.length < 2) return; // 少于两个标题不显示目录
        var list = $('.lylme-toc-list', toc);
        if (!list) return;
        list.innerHTML = '';
        var frag = doc.createDocumentFragment();
        heads.forEach(function (h, i) {
            if (!h.id) h.id = 'lylme-h-' + (i + 1);
            var li = doc.createElement('li');
            var a = doc.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent;
            a.className = 'lvl-' + parseInt(h.tagName.substring(1), 10);
            a.addEventListener('click', function (e) {
                e.preventDefault();
                var t = doc.getElementById(h.id);
                if (t) t.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
            });
            li.appendChild(a);
            frag.appendChild(li);
        });
        list.appendChild(frag);
        toc.hidden = false;

        if (read('lylme-toc-collapsed') === '1') toc.classList.add('collapsed');
        var toggle = $('.lylme-toc-toggle', toc);
        if (toggle) {
            toggle.addEventListener('click', function () {
                var c = toc.classList.toggle('collapsed');
                store('lylme-toc-collapsed', c ? '1' : '0');
            });
        }

        // 滚动高亮
        var links = $$('a', list);
        function setActive(id) {
            links.forEach(function (a) { a.classList.toggle('active', a.getAttribute('href') === '#' + id); });
        }
        if ('IntersectionObserver' in window) {
            var visible = {};
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) { visible[en.target.id] = en.isIntersecting; });
                for (var j = 0; j < heads.length; j++) {
                    if (visible[heads[j].id]) { setActive(heads[j].id); break; }
                }
            }, { rootMargin: '0px 0px -70% 0px', threshold: 0 });
            heads.forEach(function (h) { io.observe(h); });
        }
    }

    /* =========================================================
       6. 阅读进度条
       ========================================================= */
    function initProgress() {
        var bar = $('#lylme-progress-bar');
        var article = $('#lylme-post-content');
        if (!bar || !article) return;
        var ticking = false;
        function update() {
            var rect = article.getBoundingClientRect();
            var total = article.offsetHeight - window.innerHeight + article.offsetTop;
            var start = article.offsetTop;
            var pos = window.scrollY - start;
            var pct = Math.max(0, Math.min(100, (pos / Math.max(1, total - start)) * 100));
            bar.style.width = pct + '%';
            ticking = false;
        }
        window.addEventListener('scroll', function () {
            if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        update();
    }

    /* =========================================================
       7. 返回顶部
       ========================================================= */
    function initBacktop() {
        var btn = $('#lylme-backtop');
        if (!btn) return;
        function onScroll() {
            btn.classList.toggle('show', window.scrollY > 400);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }

    /* =========================================================
       8. 代码块复制按钮
       ========================================================= */
    function initCodeCopy() {
        var body = $('#lylme-post-content');
        if (!body) return;
        $$('pre', body).forEach(function (pre) {
            if ($('.lylme-copy-btn', pre)) return;
            var code = pre.querySelector('code') || pre;
            var btn = doc.createElement('button');
            btn.type = 'button';
            btn.className = 'lylme-copy-btn';
            btn.textContent = '复制';
            btn.addEventListener('click', function () {
                copyText(code.innerText, function (ok) {
                    btn.textContent = ok ? '已复制' : '失败';
                    btn.classList.toggle('copied', !!ok);
                    toast(ok ? '代码已复制' : '复制失败, 请手动选择');
                    setTimeout(function () { btn.textContent = '复制'; btn.classList.remove('copied'); }, 1800);
                });
            });
            pre.appendChild(btn);
        });
    }

    /* =========================================================
       9. 分享 / 打印
       ========================================================= */
    function initSharePrint() {
        var share = $('.lylme-share');
        if (share) {
            share.addEventListener('click', function () {
                copyText(window.location.href, function (ok) {
                    toast(ok ? '链接已复制, 去分享吧' : '复制失败');
                });
            });
        }
        var print = $('.lylme-print');
        if (print) print.addEventListener('click', function () { window.print(); });
    }

    /* =========================================================
       10. 侧栏开合记忆 (与基座 lyear-aside-toggler 协作)
       ========================================================= */
    function initSidebarMemory() {
        var CLOSED = 'lyear-layout-sidebar-close';
        // 恢复 (仅桌面)
        if (window.innerWidth >= 992 && read('lylme-sidebar') === 'closed') {
            doc.body.classList.add(CLOSED);
        }
        var toggler = $('.lyear-aside-toggler');
        if (toggler) {
            toggler.addEventListener('click', function () {
                setTimeout(function () {
                    var closed = doc.body.classList.contains(CLOSED) && window.innerWidth >= 992;
                    store('lylme-sidebar', closed ? 'closed' : 'open');
                }, 0);
            });
        }
    }

    /* =========================================================
       11. 入视淡上动画 (不阻塞: 无 IO 或减少动效则跳过)
       ========================================================= */
    function initReveal() {
        if (!('IntersectionObserver' in window) || reduceMotion) return;
        var targets = $$('.lylme-post, .lylme-widget, .lylme-single, .lylme-hero, .lylme-near-item').filter(function (el) {
            return !!(el.offsetParent || el.getClientRects().length);
        });
        if (!targets.length) return;
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('lylme-inview');
                    obs.unobserve(en.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        targets.forEach(function (el, i) {
            el.classList.add('lylme-anim');
            // 同屏多个卡片时错峰入场, 避免整页同时闪现
            el.style.animationDelay = (Math.min(i, 6) * 55) + 'ms';
            io.observe(el);
        });
    }

    /* =========================================================
       12. 顶栏滚动态 (玻璃底 + 阴影)
       ========================================================= */
    function initTopbarScroll() {
        var bar = $('.lylme-topbar');
        if (!bar) return;
        function onScroll() {
            bar.classList.toggle('scrolled', window.scrollY > 12);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    /* =========================================================
       13. Hero 统计数字滚动 (data-count)
       ========================================================= */
    function initCounters() {
        var nodes = $$('[data-count]');
        if (!nodes.length || reduceMotion || !('IntersectionObserver' in window)) return;
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                obs.unobserve(en.target);
                run(en.target);
            });
        }, { threshold: 0.4 });
        nodes.forEach(function (n) { io.observe(n); });

        function run(el) {
            var to = parseInt(el.getAttribute('data-count'), 10);
            if (!isFinite(to) || to <= 0 || to > 999999) return;
            var dur = 900, t0 = null;
            function step(ts) {
                if (t0 === null) t0 = ts;
                var p = Math.min(1, (ts - t0) / dur);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(to * eased);
                if (p < 1) window.requestAnimationFrame(step);
            }
            window.requestAnimationFrame(step);
        }
    }

    /* =========================================================
       14. 侧栏子菜单开合
       触发器是 <button class="nav-subnav-toggle"> (不导航, 故不用空链 a),
       而基座只绑了 .nav-item-has-subnav > a, 所以这里自行接管。
       ========================================================= */
    function initSubnav() {
        function setOpen(li, open) {
            li.classList.toggle('open', open);
            var trigger = li.querySelector('.nav-subnav-toggle');
            if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        doc.addEventListener('click', function (e) {
            var btn = (e.target && e.target.closest) ? e.target.closest('.nav-subnav-toggle') : null;
            if (!btn) return;
            var li = btn.parentNode;
            if (!li || !li.classList || !li.classList.contains('nav-item-has-subnav')) return;
            var willOpen = !li.classList.contains('open');
            // 手风琴: 同层只保留一项展开, 与基座原行为一致
            var sibs = li.parentNode ? li.parentNode.children : [];
            for (var i = 0; i < sibs.length; i++) {
                if (sibs[i] !== li && sibs[i].classList && sibs[i].classList.contains('open')) {
                    setOpen(sibs[i], false);
                }
            }
            setOpen(li, willOpen);
        });
    }

    /* =========================================================
       启动
       ========================================================= */
    function boot() {
        initScheme();
        initSidebarMemory();
        initSubnav();
        initToc();
        initProgress();
        initBacktop();
        initCodeCopy();
        initSharePrint();
        initReveal();
        initTopbarScroll();
        initCounters();
    }

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
