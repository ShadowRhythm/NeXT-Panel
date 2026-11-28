{include file='components/header.tpl'}
{include file='components/hero.tpl' hero_title=('email.verify_email_title'|trans)}
<p style="margin:0 0 14px;">{trans key='email.verify_code_text' code=$code}</p>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:6px 0 16px;">
    <tr>
        <td align="center" style="background-color:#ecf3fb;border-radius:8px;padding:18px 12px;font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;font-size:28px;font-weight:700;letter-spacing:6px;color:#1a5db0;">{$code}</td>
    </tr>
</table>
<p style="margin:0;font-size:13px;color:#667085;text-align:center;">{trans key='email.verify_code_expire' expire=$expire}</p>
<p style="margin:10px 0 0;font-size:13px;color:#667085;text-align:center;">{trans key='email.verify_code_ignore'}</p>
{include file='components/footer.tpl'}
