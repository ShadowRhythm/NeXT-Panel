{include file='components/header.tpl'}
{include file='components/hero.tpl' hero_title=('email.password_reset_title'|trans)}
{if isset($user)}<p style="margin:0 0 14px;font-size:17px;font-weight:700;color:#1f2937;">{$user->user_name}</p>{/if}
<p style="margin:0;">{trans key='email.password_reset_desc' app_name=$config['appName']}</p>
{include file='components/button.tpl' btn_text=('email.click_to_reset_password'|trans) btn_url=$resetUrl}
<p style="margin:14px 0 0;font-size:12px;line-height:18px;color:#667085;word-break:break-all;text-align:center;">{$resetUrl}</p>
{include file='components/footer.tpl'}
