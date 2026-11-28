{include file='components/header.tpl'}
{include file='components/hero.tpl' hero_title=('email.account_created_title'|trans)}
{if isset($user)}<p style="margin:0 0 14px;font-size:17px;font-weight:700;color:#1f2937;">{$user->user_name}</p>{/if}
<p style="margin:0;">{$text}</p>
{include file='components/button.tpl' btn_text=$config['appName'] btn_url="{$config['baseUrl']}/user"}
{include file='components/footer.tpl'}
