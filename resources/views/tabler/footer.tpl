<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3 shadow-sm bg-body"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-language me-1"></i>
            {if $current_locale === 'zh_CN'}简体中文
            {elseif $current_locale === 'zh_TW'}繁體中文
            {elseif $current_locale === 'en_US'}English
            {elseif $current_locale === 'ja_JP'}日本語
            {elseif $current_locale === 'ko_KR'}한국어
            {elseif $current_locale === 'ru_RU'}Русский
            {elseif $current_locale === 'fr_FR'}Français
            {elseif $current_locale === 'de_DE'}Deutsch
            {elseif $current_locale === 'es_ES'}Español
            {elseif $current_locale === 'pt_BR'}Português
            {elseif $current_locale === 'vi_VN'}Tiếng Việt
            {elseif $current_locale === 'id_ID'}Bahasa Indonesia
            {elseif $current_locale === 'tr_TR'}Türkçe
            {elseif $current_locale === 'ar_SA'}العربية
            {elseif $current_locale === 'fa_IR'}فارسی
            {else}{$current_locale}
            {/if}
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow">
            <a class="dropdown-item {if $current_locale === 'zh_CN'}active{/if}" href="/locale/zh_CN">🇨🇳 简体中文</a>
            <a class="dropdown-item {if $current_locale === 'zh_TW'}active{/if}" href="/locale/zh_TW">🇭🇰 繁體中文</a>
            <a class="dropdown-item {if $current_locale === 'en_US'}active{/if}" href="/locale/en_US">🇺🇸 English</a>
            <a class="dropdown-item {if $current_locale === 'ja_JP'}active{/if}" href="/locale/ja_JP">🇯🇵 日本語</a>
            <a class="dropdown-item {if $current_locale === 'ko_KR'}active{/if}" href="/locale/ko_KR">🇰🇷 한국어</a>
            <a class="dropdown-item {if $current_locale === 'ru_RU'}active{/if}" href="/locale/ru_RU">🇷🇺 Русский</a>
            <a class="dropdown-item {if $current_locale === 'fr_FR'}active{/if}" href="/locale/fr_FR">🇫🇷 Français</a>
            <a class="dropdown-item {if $current_locale === 'de_DE'}active{/if}" href="/locale/de_DE">🇩🇪 Deutsch</a>
            <a class="dropdown-item {if $current_locale === 'es_ES'}active{/if}" href="/locale/es_ES">🇪🇸 Español</a>
            <a class="dropdown-item {if $current_locale === 'pt_BR'}active{/if}" href="/locale/pt_BR">🇧🇷 Português</a>
            <a class="dropdown-item {if $current_locale === 'vi_VN'}active{/if}" href="/locale/vi_VN">🇻🇳 Tiếng Việt</a>
            <a class="dropdown-item {if $current_locale === 'id_ID'}active{/if}" href="/locale/id_ID">🇮🇩 Bahasa Indonesia</a>
            <a class="dropdown-item {if $current_locale === 'tr_TR'}active{/if}" href="/locale/tr_TR">🇹🇷 Türkçe</a>
            <a class="dropdown-item {if $current_locale === 'ar_SA'}active{/if}" href="/locale/ar_SA">🇸🇦 العربية</a>
            <a class="dropdown-item {if $current_locale === 'fa_IR'}active{/if}" href="/locale/fa_IR">🇮🇷 فارسی</a>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="success-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-circle-check icon mb-2 text-green icon-lg" style="font-size:3.5rem;"></i>
                <p id="success-message" class="text-secondary">{trans key='common.success'}</p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <a id="success-confirm" href="" class="btn w-100" data-bs-dismiss="modal">
                                {trans key='common.ok'}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="fail-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-circle-x icon mb-2 text-danger icon-lg" style="font-size:3.5rem;"></i>
                <p id="fail-message" class="text-secondary">{trans key='common.error'}</p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <a href="" class="btn btn-danger w-100" data-bs-dismiss="modal">
                                {trans key='common.confirm'}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<script>
    let Modal = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
        || (typeof tabler !== 'undefined' && (tabler.Modal || (tabler.bootstrap && tabler.bootstrap.Modal)));
    let successDialog = Modal ? new Modal(document.getElementById('success-dialog')) : null;
    let failDialog = Modal ? new Modal(document.getElementById('fail-dialog')) : null;

    htmx.on("htmx:afterRequest", function(evt) {
        if (evt.detail.xhr.getResponseHeader('HX-Redirect'))
        {
            return;
        }

        try {
            if (!evt.detail.xhr.response || evt.detail.xhr.response.trim() === '') {
                return;
            }

            let res = JSON.parse(evt.detail.xhr.response);

            if (evt.detail.elt.id === 'send-verify-email') {
                document.getElementById('send-verify-email').disabled = true;
            }

            if (res.ret === 1) {
                document.getElementById("success-message").innerHTML = res.msg;
                if (successDialog) successDialog.show();
            } else {
                document.getElementById("fail-message").innerHTML = res.msg;
                if (failDialog) failDialog.show();
            }
        } catch (e) {
            console.error("HTMX request error:", e, evt.detail.xhr);
            let errMsg = evt.detail.xhr ? evt.detail.xhr.getResponseHeader('X-Error-Message') : null;
            let errFile = evt.detail.xhr ? evt.detail.xhr.getResponseHeader('X-Error-File') : null;
            if (errMsg) {
                let msgText = decodeURIComponent(errMsg);
                if (errFile) {
                    msgText += '<br><small class="text-secondary">' + decodeURIComponent(errFile) + '</small>';
                }
                document.getElementById("fail-message").innerHTML = msgText;
            } else {
                document.getElementById("fail-message").innerHTML = "{trans key='common.unexpected_error' escape='js'}";
            }
            if (failDialog) failDialog.show();
        }
    });
</script>

{include file='live_chat.tpl'}

{include file='telemetry.tpl'}

</body>

</html>
